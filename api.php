<?php

declare(strict_types=1);

const STORAGE_FILE = __DIR__ . '/tasks_data.json';
const CURRENT_USER_ID = 1;

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

function leer_tareas(): array
{
    if (!is_file(STORAGE_FILE)) {
        return [];
    }

    $contenido = file_get_contents(STORAGE_FILE);
    $tareas = json_decode($contenido ?: '[]', true);
    return is_array($tareas) ? $tareas : [];
}

function guardar_tareas(array $tareas): void
{
    file_put_contents(
        STORAGE_FILE,
        json_encode(array_values($tareas), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    );
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

function indice_tarea_propia(array $tareas, int $id): int
{
    foreach ($tareas as $indice => $tarea) {
        if ($tarea['id'] === $id) {
            if ($tarea['usuario_id'] !== CURRENT_USER_ID) {
                error_api(403, 'No tienes permiso para modificar esta tarea.', ['id: pertenece a otro usuario']);
            }
            return $indice;
        }
    }

    error_api(404, 'La tarea no existe.', ['id: no encontrado']);
}

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$ruta = rtrim($ruta, '/') ?: '/';
$tareas = leer_tareas();

if ($metodo === 'GET' && $ruta === '/tasks') {
    $propias = array_map(
        'datos_publicos',
        array_filter($tareas, fn (array $tarea): bool => $tarea['usuario_id'] === CURRENT_USER_ID)
    );
    responder(200, array_values($propias));
}

if ($metodo === 'GET' && str_starts_with($ruta, '/tasks/')) {
    $id = id_de_ruta($ruta);
    $tarea = null;
    foreach ($tareas as $elemento) {
        if ($elemento['id'] === $id) {
            $tarea = $elemento;
            break;
        }
    }
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
    foreach ($tareas as $tarea) {
        if ($tarea['usuario_id'] === CURRENT_USER_ID
            && $tarea['titulo'] === $datos['titulo']
            && $tarea['fecha_limite'] === $datos['fecha_limite']) {
            error_api(409, 'La tarea ya existe.', ['titulo y fecha_limite: ya están registrados']);
        }
    }

    $ids = array_column($tareas, 'id');
    $nuevo_id = $ids === [] ? 1 : max($ids) + 1;
    $tarea = ['id' => $nuevo_id, 'usuario_id' => CURRENT_USER_ID, ...$datos];
    $tareas[] = $tarea;
    guardar_tareas($tareas);
    responder(201, datos_publicos($tarea));
}

if ($metodo === 'PUT' && str_starts_with($ruta, '/tasks/')) {
    $id = id_de_ruta($ruta);
    $datos = validar_tarea(leer_json());
    $indice = indice_tarea_propia($tareas, $id);
    foreach ($tareas as $tarea) {
        if ($tarea['id'] !== $id
            && $tarea['usuario_id'] === CURRENT_USER_ID
            && $tarea['titulo'] === $datos['titulo']
            && $tarea['fecha_limite'] === $datos['fecha_limite']) {
            error_api(409, 'Ya tienes otra tarea con ese título y fecha.', ['titulo y fecha_limite: ya están registrados']);
        }
    }

    $tareas[$indice] = ['id' => $id, 'usuario_id' => CURRENT_USER_ID, ...$datos];
    guardar_tareas($tareas);
    responder(200, datos_publicos($tareas[$indice]));
}

if ($metodo === 'DELETE' && str_starts_with($ruta, '/tasks/')) {
    $id = id_de_ruta($ruta);
    $indice = indice_tarea_propia($tareas, $id);
    unset($tareas[$indice]);
    guardar_tareas($tareas);
    http_response_code(204);
    exit;
}

if (str_starts_with($ruta, '/tasks')) {
    error_api(405, 'Método no permitido.', ['método: usa GET, POST, PUT o DELETE según la ruta']);
}

error_api(404, 'La ruta no existe.', ['ruta: no encontrada']);