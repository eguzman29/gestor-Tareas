<?php

declare(strict_types=1);

const DATABASE_FILE = __DIR__ . '/tasks.sqlite';
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'clave-de-desarrollo-cambiala-en-produccion-2026');

$ruta_actual = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (rtrim($ruta_actual, '/') === '/docs') {
    header('Content-Type: text/html; charset=utf-8');
    echo <<<'HTML'
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API de Tareas - Swagger UI</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        window.onload = () => SwaggerUIBundle({
            url: "/openapi.json",
            dom_id: "#swagger-ui",
            deepLinking: true,
            presets: [SwaggerUIBundle.presets.apis]
        });
    </script>
</body>
</html>
HTML;
    exit;
}

if (rtrim($ruta_actual, '/') === '/openapi.json') {
    $nombre_contrato = 'openapi.json';
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

function longitud_texto(string $texto): int
{
    return function_exists('mb_strlen') ? mb_strlen($texto) : strlen($texto);
}

function recortar_texto(string $texto, int $limite): string
{
    return function_exists('mb_substr') ? mb_substr($texto, 0, $limite) : substr($texto, 0, $limite);
}

function preguntar_a_la_ia(string $instruccion): array
{
    $url = rtrim(getenv('EXTERNO_URL') ?: 'https://generativelanguage.googleapis.com', '/');
    $modelo = getenv('GEMINI_MODELO') ?: 'gemini-flash-lite-latest';
    $clave = getenv('GEMINI_API_KEY') ?: '';
    if (!function_exists('curl_init')) {
        error_api(503, 'La integración con IA no está disponible.', ['servidor: falta la extensión cURL de PHP']);
    }
    if ($clave === '' && str_contains($url, 'googleapis.com')) {
        error_api(503, 'Falta configurar la variable GEMINI_API_KEY.', []);
    }

    $peticion = json_encode([
        'contents' => [['parts' => [['text' => $instruccion]]]],
        'generationConfig' => ['responseMimeType' => 'application/json', 'temperature' => 0],
    ]);
    for ($intento = 0; $intento < 2; $intento++) {
        $ch = curl_init("$url/v1beta/models/$modelo:generateContent");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', "x-goog-api-key: $clave"],
            CURLOPT_POSTFIELDS => $peticion,
        ]);
        $texto = curl_exec($ch);
        $errno = curl_errno($ch);
        $estado = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($intento === 0 && in_array($estado, [429, 503], true)) {
            continue;
        }
        break;
    }

    if ($errno === CURLE_OPERATION_TIMEDOUT) {
        error_api(504, 'La IA tardó demasiado en responder.', []);
    }
    if ($errno !== 0) {
        error_api(502, 'No se pudo conectar con la IA.', []);
    }
    if ($estado >= 400) {
        error_api(502, "La IA no está disponible (respondió $estado).", []);
    }

    $respuesta = json_decode($texto ?: '', true);
    $contenido = $respuesta['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $ia = json_decode($contenido, true);
    if (!is_array($ia)) {
        error_api(502, 'La IA respondió algo que no entendemos.', []);
    }
    return $ia;
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
    $db->exec(
        'CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario TEXT NOT NULL UNIQUE,
            clave_hash TEXT NOT NULL,
            rol TEXT NOT NULL
        )'
    );
    sembrar_usuarios($db);

    return $db;
}

function sembrar_usuarios(PDO $db): void
{
    if ((int) $db->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() > 0) {
        return;
    }
    $usuarios = [
        ['encargado', 'encargado123', 'admin'],
        ['ana', 'ana12345', 'usuario'],
        ['beto', 'beto12345', 'usuario'],
    ];
    $consulta = $db->prepare(
        'INSERT OR IGNORE INTO usuarios (usuario, clave_hash, rol) VALUES (?, ?, ?)'
    );
    foreach ($usuarios as [$usuario, $clave, $rol]) {
        $consulta->execute([$usuario, password_hash($clave, PASSWORD_BCRYPT), $rol]);
    }
}

function base64url_encode(string $valor): string
{
    return rtrim(strtr(base64_encode($valor), '+/', '-_'), '=');
}

function base64url_decode(string $valor): string|false
{
    return base64_decode(strtr($valor, '-_', '+/') . str_repeat('=', (4 - strlen($valor) % 4) % 4), true);
}

function firmar_jwt(array $datos): string
{
    $cabecera = base64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $cuerpo = base64url_encode(json_encode($datos));
    $firma = base64url_encode(hash_hmac('sha256', "$cabecera.$cuerpo", JWT_SECRET, true));
    return "$cabecera.$cuerpo.$firma";
}

function verificar_jwt(string $token): ?array
{
    $partes = explode('.', $token);
    if (count($partes) !== 3) {
        return null;
    }
    $esperada = base64url_encode(hash_hmac('sha256', "$partes[0].$partes[1]", JWT_SECRET, true));
    if (!hash_equals($esperada, $partes[2])) {
        return null;
    }
    $datos = json_decode(base64url_decode($partes[1]) ?: '', true);
    if (!is_array($datos) || !isset($datos['sub'], $datos['rol'], $datos['exp']) || $datos['exp'] < time()) {
        return null;
    }
    return $datos;
}

function usuario_actual(): array
{
    $encabezados = function_exists('getallheaders') ? getallheaders() : [];
    $autorizacion = $_SERVER['HTTP_AUTHORIZATION'] ?? ($encabezados['Authorization'] ?? '');
    if (!preg_match('/^Bearer\s+(\S+)$/i', $autorizacion, $coincidencias)) {
        error_api(401, 'Falta el token.', ['Authorization: usa Bearer <token>']);
    }
    $datos = verificar_jwt($coincidencias[1]);
    if ($datos === null) {
        error_api(401, 'El token no es válido o ya expiró.', ['token: inválido']);
    }
    return $datos;
}

function solo_admin(array $yo): void
{
    if ($yo['rol'] !== 'admin') {
        error_api(403, 'Tu rol no puede hacer esto.', ['rol: permiso insuficiente']);
    }
}

function usuario_id(PDO $db, string $nombre): int
{
    $consulta = $db->prepare('SELECT id FROM usuarios WHERE usuario = ?');
    $consulta->execute([$nombre]);
    $id = $consulta->fetchColumn();
    if ($id === false) {
        error_api(401, 'El token no es válido.', ['usuario: no existe']);
    }
    return (int) $id;
}

function leer_tareas(PDO $db, ?int $usuario_id): array
{
    if ($usuario_id === null) {
        return $db->query(
            'SELECT id, usuario_id, titulo, estado, prioridad, fecha_limite
             FROM tasks ORDER BY id'
        )->fetchAll();
    }
    $consulta = $db->prepare(
        'SELECT id, usuario_id, titulo, estado, prioridad, fecha_limite
         FROM tasks WHERE usuario_id = ? ORDER BY id'
    );
    $consulta->execute([$usuario_id]);
    return $consulta->fetchAll();
}

function buscar_tarea(PDO $db, int $id, ?int $usuario_id): ?array
{
    if ($usuario_id === null) {
        $consulta = $db->prepare(
            'SELECT id, usuario_id, titulo, estado, prioridad, fecha_limite
             FROM tasks WHERE id = ?'
        );
        $consulta->execute([$id]);
        $tarea = $consulta->fetch();
        return $tarea === false ? null : $tarea;
    }
    $consulta = $db->prepare(
        'SELECT id, usuario_id, titulo, estado, prioridad, fecha_limite
         FROM tasks WHERE id = ? AND usuario_id = ?'
    );
    $consulta->execute([$id, $usuario_id]);
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

    return buscar_tarea($db, (int) $db->lastInsertId(), $usuario_id);
}

function actualizar_tarea(PDO $db, int $id, array $datos, int $usuario_id): array
{
    $consulta = $db->prepare(
        'UPDATE tasks
         SET titulo = ?, estado = ?, prioridad = ?, fecha_limite = ?
         WHERE id = ? AND usuario_id = ?'
    );
    $consulta->execute([
        $datos['titulo'],
        $datos['estado'],
        $datos['prioridad'],
        $datos['fecha_limite'],
        $id,
        $usuario_id,
    ]);

    return buscar_tarea($db, $id, $usuario_id);
}

function borrar_tarea(PDO $db, int $id, int $usuario_id): void
{
    $consulta = $db->prepare('DELETE FROM tasks WHERE id = ? AND usuario_id = ?');
    $consulta->execute([$id, $usuario_id]);
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

// ---------- Sesión 5: preguntarle algo a la IA (Gemini) ----------
const INSTRUCCION_IA = 'Eres un asistente de productividad. Para la tarea que te doy, responde SOLO un JSON con la forma {"prioridad": "alta" | "media" | "baja", "razon": "máximo 15 palabras en español"}.' . "\n" . 'Tarea: ';

function preguntar_prioridad_a_la_ia(string $instruccion): array
{
    $url = getenv('EXTERNO_URL') ?: 'https://generativelanguage.googleapis.com';  // en pruebas: el simulador
    $modelo = getenv('GEMINI_MODELO') ?: 'gemini-flash-lite-latest';
    $clave = getenv('GEMINI_API_KEY') ?: '';                 // IDEA 3: la clave NO va en el código
    if ($clave === '' && str_contains($url, 'googleapis.com')) {
        error_api(503, 'Falta configurar la variable de entorno GEMINI_API_KEY.', ['GEMINI_API_KEY: no definida']);
    }
    $ch = curl_init("$url/v1beta/models/$modelo:generateContent");   // IDEA 1: nuestra API llama a otra API
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,                                // IDEA 2: máximo 5 segundos esperando
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', "x-goog-api-key: $clave"],
        CURLOPT_POSTFIELDS => json_encode([
            'contents' => [['parts' => [['text' => $instruccion]]]],
            'generationConfig' => ['responseMimeType' => 'application/json', 'temperature' => 0],
        ]),
    ]);
    // Nivel 2: reintentar solo fallas pasajeras (429 y 5xx), con espera creciente
    for ($intento = 1; $intento <= 3; $intento++) {
        $texto = curl_exec($ch);
        $errno = curl_errno($ch);
        $estado = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($errno === CURLE_OPERATION_TIMEDOUT) {
            error_api(504, 'La IA tardó demasiado en responder. Intenta en un momento.', []);
        }
        if ($errno) {
            error_api(502, 'No se pudo conectar con la IA.', []);
        }
        if (!in_array($estado, [429, 500, 502, 503, 504], true) || $intento === 3) {
            break;                                           // éxito, o un error que no se arregla solo (400, 404...)
        }
        usleep((int) (500000 * 2 ** ($intento - 1)));        // espera 0.5 s, luego 1 s
    }
    if ($estado >= 400) {
        error_api(502, "La IA no está disponible (respondió $estado). Intenta más tarde.", []);
    }
    $respuesta = json_decode((string) $texto, true);
    $ia = json_decode($respuesta['candidates'][0]['content']['parts'][0]['text'] ?? '', true);
    if (!is_array($ia)) {
        error_api(502, 'La IA respondió algo que no entendemos.', []);
    }
    return $ia;
}

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ruta = $ruta_actual;
$ruta = rtrim($ruta, '/') ?: '/';
$db = conectar_db();

if ($metodo === 'POST' && $ruta === '/login') {
    $datos = leer_json();
    $consulta = $db->prepare('SELECT * FROM usuarios WHERE usuario = ?');
    $consulta->execute([(string) ($datos['usuario'] ?? '')]);
    $usuario = $consulta->fetch();
    if ($usuario === false || !isset($datos['clave']) || !is_string($datos['clave'])
        || !password_verify($datos['clave'], $usuario['clave_hash'])) {
        error_api(401, 'Usuario o clave incorrectos.', []);
    }
    $ahora = time();
    responder(200, [
        'access_token' => firmar_jwt([
            'sub' => $usuario['usuario'],
            'rol' => $usuario['rol'],
            'iat' => $ahora,
            'exp' => $ahora + 1800,
        ]),
        'token_type' => 'bearer',
    ]);
}

$yo = usuario_actual();
$yo_id = usuario_id($db, $yo['sub']);
$alcance = $yo['rol'] === 'admin' ? null : $yo_id;

if ($metodo === 'POST' && $ruta === '/reportes') {
    $datos = leer_json();
    $descripcion = $datos['descripcion'] ?? null;
    if (!is_string($descripcion) || longitud_texto(trim($descripcion)) < 2 || longitud_texto($descripcion) > 300) {
        error_api(422, 'Los datos no son válidos.', ['descripcion: debe tener de 2 a 300 caracteres']);
    }

    $instruccion = 'Analiza esta descripción de una tarea y responde únicamente con un JSON válido, sin markdown, '
        . 'usando exactamente esta forma: {"resumen":"texto breve","prioridad":"baja|media|alta"}. '
        . 'La prioridad solo puede ser "baja", "media" o "alta". '
        . "Descripción: $descripcion";
    $ia = preguntar_a_la_ia($instruccion);
    $prioridad = $ia['prioridad'] ?? $ia['gravedad'] ?? null;
    if (!isset($ia['resumen']) || !is_string($ia['resumen'])
        || !in_array($prioridad, ['baja', 'media', 'alta'], true)) {
        error_api(502, 'La IA respondió algo inesperado.', []);
    }
    responder(200, [
        'resumen' => recortar_texto($ia['resumen'], 200),
        'prioridad' => $prioridad,
    ]);
}

if ($metodo === 'GET' && $ruta === '/tasks') {
    $propias = array_map(
        'datos_publicos',
        leer_tareas($db, $alcance)
    );
    responder(200, array_values($propias));
}

if ($metodo === 'GET' && str_starts_with($ruta, '/tasks/')) {
    $id = id_de_ruta($ruta);
    $tarea = buscar_tarea($db, $id, $alcance);
    if ($tarea === null) {
        error_api(404, 'La tarea no existe.', ['id: no encontrado']);
    }
    responder(200, datos_publicos($tarea));
}

if ($metodo === 'POST' && $ruta === '/tasks') {
    $datos = validar_tarea(leer_json());
    if (existe_duplicado($db, $datos, $yo_id)) {
        error_api(409, 'La tarea ya existe.', ['titulo y fecha_limite: ya están registrados']);
    }

    try {
        $tarea = guardar_tareas($db, $datos, $yo_id);
    } catch (PDOException $e) {
        error_api(409, 'La tarea ya existe.', ['titulo y fecha_limite: ya están registrados']);
    }
    responder(201, datos_publicos($tarea));
}

if ($metodo === 'PUT' && str_starts_with($ruta, '/tasks/')) {
    solo_admin($yo);
    $id = id_de_ruta($ruta);
    $datos = validar_tarea(leer_json());
    $tarea_actual = buscar_tarea($db, $id, $alcance);
    if ($tarea_actual === null) {
        error_api(404, 'La tarea no existe.', ['id: no encontrado']);
    }
    $dueno_id = (int) $tarea_actual['usuario_id'];
    if (existe_duplicado($db, $datos, $dueno_id, $id)) {
        error_api(409, 'Ya tienes otra tarea con ese título y fecha.', ['titulo y fecha_limite: ya están registrados']);
    }

    responder(200, datos_publicos(actualizar_tarea($db, $id, $datos, $dueno_id)));
}

if ($metodo === 'PATCH' && str_starts_with($ruta, '/tasks/')) {
    $id = id_de_ruta($ruta);
    $cambios = leer_json();
    $tarea_actual = buscar_tarea($db, $id, $alcance);
    if ($tarea_actual === null) {
        error_api(404, 'La tarea no existe.', ['id: no encontrado']);
    }
    $datos = validar_tarea(array_merge(datos_publicos($tarea_actual), $cambios));
    $dueno_id = (int) $tarea_actual['usuario_id'];
    if (existe_duplicado($db, $datos, $dueno_id, $id)) {
        error_api(409, 'Ya tienes otra tarea con ese título y fecha.', ['titulo y fecha_limite: ya están registrados']);
    }

    responder(200, datos_publicos(actualizar_tarea($db, $id, $datos, $dueno_id)));
}

if ($metodo === 'DELETE' && str_starts_with($ruta, '/tasks/')) {
    $id = id_de_ruta($ruta);
    $tarea = buscar_tarea($db, $id, $alcance);
    if ($tarea === null) {
        error_api(404, 'La tarea no existe.', ['id: no encontrado']);
    }
    borrar_tarea($db, $id, (int) $tarea['usuario_id']);
    http_response_code(204);
    exit;
}

if ($metodo === 'POST' && $ruta === '/tareas/prioridad') {
    $datos = leer_json();
    $titulo = $datos['titulo'] ?? null;
    if (!is_string($titulo) || mb_strlen(trim($titulo)) < 3 || mb_strlen($titulo) > 100) {
        error_api(422, 'Los datos no son válidos.', ['titulo: de 3 a 100 caracteres']);
    }
    $ia = preguntar_prioridad_a_la_ia(INSTRUCCION_IA . $titulo);   // a la IA solo le mandamos el título
    // Nunca confíen en lo que responde la IA: revisen que trae lo que pidieron
    if (!in_array($ia['prioridad'] ?? null, ['alta', 'media', 'baja'], true) || empty($ia['razon'])) {
        error_api(502, 'La IA respondió algo inesperado.', []);
    }
    responder(200, ['prioridad' => $ia['prioridad'], 'razon' => mb_substr((string) $ia['razon'], 0, 200)]);
}

if (str_starts_with($ruta, '/tasks')) {
    error_api(405, 'Método no permitido.', ['método: usa GET, POST, PUT, PATCH o DELETE según la ruta']);
}

error_api(404, 'La ruta no existe.', ['ruta: no encontrada']);