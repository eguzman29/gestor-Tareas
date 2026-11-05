<?php

declare(strict_types=1);

const DATABASE_FILE = __DIR__ . '/tasks.sqlite';
const CURRENT_USER_ID = 1;

$ruta_actual = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (rtrim($ruta_actual, '/') === '/docs' || rtrim($ruta_actual, '/') === '/openapi.json') {
    $nombre_contrato = rtrim($ruta_actual, '/') === '/openapi.json' ? 'openapi.json' : 'openapi.yaml';
    $contrato = file_get_contents(__DIR__ . '/' . $nombre_contrato);
    if ($contrato === false) {
        http_response_code(500);
        exit('No se pudo leer el contrato OpenAPI.');
    }
    header('Content-Type: ' . ($nombre_contrato === 'openapi.json' ? 'application/json' : 'application/yaml') . '; charset=utf-8');
    echo $contrato;
    exit;
}

header('Content-Type: application/json; charset=utf-8');

function responder(int $codigo, mixed $cuerpo): never
{
    http_response_code($codigo);
    echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function error_api(int $codigo, string $mensaje, array $detalles = []): never
{
    responder($codigo, ['error' => $mensaje, 'detalles' => $detalles]);
}

function conectar_db(): PDO
{
    static $db = null;
    if ($db instanceof PDO) {
        return $db;
    }

    $db = new PDO('sqlite:' . DATABASE_FILE);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec(
        'CREATE TABLE IF NOT EXISTS tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            titulo TEXT NOT NULL,
            estado TEXT NOT NULL,
            prioridad INTEGER NOT NULL,
            fecha_limite TEXT NOT NULL,
            UNIQUE (usuario_id, titulo, fecha_limite)
        )'
    );

    return $db;
}

function leer_tareas(PDO $db, int $usuario_id): array
{
    $consulta = $db->prepare(
        'SELECT id, usuario_id, titulo, estado, prioridad, fecha_limite
         FROM tasks WHERE usuario_id = ? ORDER BY id'
    );
    $consulta->execute([$usuario_id]);
    return $consulta->fetchAll();
}

function buscar_tarea(PDO $db, int $id): ?array
{
    $consulta = $db->prepare(
        'SELECT id, usuario_id, titulo, estado, prioridad, fecha_limite
         FROM tasks WHERE id = ?'
    );
    $consulta->execute([$id]);
    $tarea = $consulta->fetch();
    return $tarea === false ? null : $tarea;
}

function existe_duplicado(PDO $db, array $datos, int $usuario_id, ?int $excluir_id = null): bool
{
    $sql = 'SELECT id FROM tasks
            WHERE usuario_id = ? AND titulo = ? AND fecha_limite = ?';
    $parametros = [$usuario_id, $datos['titulo'], $datos['fecha_limite']];
    if ($excluir_id !== null) {
        $sql .= ' AND id <> ?';
        $parametros[] = $excluir_id;
    }

    $consulta = $db->prepare($sql);
    $consulta->execute($parametros);
    return $consulta->fetch() !== false;
}

function guardar_tareas(PDO $db, array $datos, int $usuario_id): array
{
    $consulta = $db->prepare(
        'INSERT INTO tasks (usuario_id, titulo, estado, prioridad, fecha_limite)
         VALUES (?, ?, ?, ?, ?)'
    );
    $consulta->execute([
        $usuario_id,
        $datos['titulo'],
        $datos['estado'],
        $datos['prioridad'],
        $datos['fecha_limite'],
    ]);

    return buscar_tarea($db, (int) $db->lastInsertId());
}

function actualizar_tarea(PDO $db, int $id, array $datos): array
{
    $consulta = $db->prepare(
        'UPDATE tasks
         SET titulo = ?, estado = ?, prioridad = ?, fecha_limite = ?
         WHERE id = ?'
    );
    $consulta->execute([
        $datos['titulo'],
        $datos['estado'],
        $datos['prioridad'],
        $datos['fecha_limite'],
        $id,
    ]);

    return buscar_tarea($db, $id);
}

function borrar_tarea(PDO $db, int $id): void
{
    $consulta = $db->prepare('DELETE FROM tasks WHERE id = ?');
    $consulta->execute([$id]);
}

function datos_publicos(array $tarea): array
{
    unset($tarea['usuario_id']);
    return $tarea;
}

function leer_json(): array
{
    $cuerpo = file_get_contents('php://input');
    $datos = json_decode($cuerpo ?: '', true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_api(400, 'El cuerpo debe ser un JSON válido.', ['cuerpo: JSON mal formado']);
    }
    if (!is_array($datos)) {
        error_api(422, 'El cuerpo tiene una forma inválida.', ['cuerpo: debe ser un objeto JSON']);
    }

    return $datos;
}

function validar_tarea(array $datos): array
{
    $detalles = [];
    $campos = ['titulo', 'estado', 'prioridad', 'fecha_limite'];

    foreach ($campos as $campo) {
        if (!array_key_exists($campo, $datos)) {
            $detalles[] = "$campo: es obligatorio";
        }
    }

    if (array_key_exists('titulo', $datos)
        && (!is_string($datos['titulo']) || strlen($datos['titulo']) < 3 || strlen($datos['titulo']) > 100)) {
        $detalles[] = 'titulo: debe ser texto de 3 a 100 caracteres';
    }

    $estados = ['pendiente', 'en_progreso', 'terminada'];
    if (array_key_exists('estado', $datos)
        && (!is_string($datos['estado']) || !in_array($datos['estado'], $estados, true))) {
        $detalles[] = 'estado: debe ser pendiente, en_progreso o terminada';
    }

    if (array_key_exists('prioridad', $datos)
        && (is_bool($datos['prioridad']) || !is_int($datos['prioridad']) || $datos['prioridad'] < 1 || $datos['prioridad'] > 5)) {
        $detalles[] = 'prioridad: debe ser un entero entre 1 y 5';
    }

    $fecha_valida = false;
    if (array_key_exists('fecha_limite', $datos) && is_string($datos['fecha_limite'])) {
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $datos['fecha_limite']);
        $fecha_valida = $fecha !== false && $fecha->format('Y-m-d') === $datos['fecha_limite'];
        if ($fecha_valida && $datos['fecha_limite'] < date('Y-m-d')) {
            $fecha_valida = false;
            $detalles[] = 'fecha_limite: no puede ser anterior a hoy';
        }
    }
    if (array_key_exists('fecha_limite', $datos) && !$fecha_valida
        && !in_array('fecha_limite: no puede ser anterior a hoy', $detalles, true)) {
        $detalles[] = 'fecha_limite: debe tener formato AAAA-MM-DD';
    }

    if ($detalles !== []) {
        error_api(422, 'Los datos de la tarea no son válidos.', $detalles);
    }

    return [
        'titulo' => $datos['titulo'],
        'estado' => $datos['estado'],
        'prioridad' => $datos['prioridad'],
        'fecha_limite' => $datos['fecha_limite'],
    ];
}

function id_de_ruta(string $ruta): int
{
    $texto_id = substr($ruta, strlen('/tasks/'));
    if ($texto_id === '' || !ctype_digit($texto_id) || (int) $texto_id < 1) {
        error_api(422, 'El id no es válido.', ['id: debe ser un entero positivo']);
    }

    return (int) $texto_id;
}

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ruta = $ruta_actual;
$ruta = rtrim($ruta, '/') ?: '/';
$db = conectar_db();

if ($metodo === 'GET' && $ruta === '/tasks') {
    $propias = array_map(
        'datos_publicos',
        leer_tareas($db, CURRENT_USER_ID)
    );
    responder(200, array_values($propias));
}

if ($metodo === 'GET' && str_starts_with($ruta, '/tasks/')) {
    $id = id_de_ruta($ruta);
    $tarea = buscar_tarea($db, $id);
    if ($tarea === null) {
        error_api(404, 'La tarea no existe.', ['id: no encontrado']);
    }
    if ($tarea['usuario_id'] !== CURRENT_USER_ID) {
        error_api(403, 'No tienes permiso para consultar esta tarea.', ['id: pertenece a otro usuario']);
    }
    responder(200, datos_publicos($tarea));
}

if ($metodo === 'POST' && $ruta === '/tasks') {
    $datos = validar_tarea(leer_json());
    if (existe_duplicado($db, $datos, CURRENT_USER_ID)) {
        error_api(409, 'La tarea ya existe.', ['titulo y fecha_limite: ya están registrados']);
    }

    try {
        $tarea = guardar_tareas($db, $datos, CURRENT_USER_ID);
    } catch (PDOException $e) {
        error_api(409, 'La tarea ya existe.', ['titulo y fecha_limite: ya están registrados']);
    }
    responder(201, datos_publicos($tarea));
}

if ($metodo === 'PUT' && str_starts_with($ruta, '/tasks/')) {
    $id = id_de_ruta($ruta);
    $datos = validar_tarea(leer_json());
    $tarea_actual = buscar_tarea($db, $id);
    if ($tarea_actual === null) {
        error_api(404, 'La tarea no existe.', ['id: no encontrado']);
    }
    if ($tarea_actual['usuario_id'] !== CURRENT_USER_ID) {
        error_api(403, 'No tienes permiso para modificar esta tarea.', ['id: pertenece a otro usuario']);
    }
    if (existe_duplicado($db, $datos, CURRENT_USER_ID, $id)) {
        error_api(409, 'Ya tienes otra tarea con ese título y fecha.', ['titulo y fecha_limite: ya están registrados']);
    }

    responder(200, datos_publicos(actualizar_tarea($db, $id, $datos)));
}

if ($metodo === 'DELETE' && str_starts_with($ruta, '/tasks/')) {
    $id = id_de_ruta($ruta);
    $tarea = buscar_tarea($db, $id);
    if ($tarea === null) {
        error_api(404, 'La tarea no existe.', ['id: no encontrado']);
    }
    if ($tarea['usuario_id'] !== CURRENT_USER_ID) {
        error_api(403, 'No tienes permiso para borrar esta tarea.', ['id: pertenece a otro usuario']);
    }
    borrar_tarea($db, $id);
    http_response_code(204);
    exit;
}

if (str_starts_with($ruta, '/tasks')) {
    error_api(405, 'Método no permitido.', ['método: usa GET, POST, PUT o DELETE según la ruta']);
}

error_api(404, 'La ruta no existe.', ['ruta: no encontrada']);