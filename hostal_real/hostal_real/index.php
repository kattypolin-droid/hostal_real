<?php
declare(strict_types=1);

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_start();
date_default_timezone_set('America/Lima');

const ADMIN = 'Administrador';
const RECEP = 'Recepcionista';
const LIMP = 'Limpieza';
const DISPONIBLE = 1;
const OCUPADA = 2;
const EN_LIMPIEZA = 3;

$baseDir = __DIR__;
$route = trim((string)($_GET['r'] ?? ''), '/');
$db = null;

function h($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $route, array $params = []): string
{
    return 'index.php?' . http_build_query(array_merge(['r' => $route], $params));
}

function db(): PDO
{
    global $db, $baseDir;
    if (!$db) {
        $db = new PDO('sqlite:' . $baseDir . DIRECTORY_SEPARATOR . 'hostal_real.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->exec('PRAGMA foreign_keys = ON');
    }
    return $db;
}

function q(string $sql, array $args = []): array
{
    $st = db()->prepare($sql);
    $st->execute($args);
    return $st->fetchAll();
}

function q1(string $sql, array $args = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($args);
    $row = $st->fetch();
    return $row ?: null;
}

function run(string $sql, array $args = []): int
{
    $st = db()->prepare($sql);
    $st->execute($args);
    return (int)db()->lastInsertId();
}

function initDb(): void
{
    $schema = [
        "CREATE TABLE IF NOT EXISTS rol (id_rol INTEGER PRIMARY KEY AUTOINCREMENT, nombre_rol TEXT NOT NULL UNIQUE)",
        "CREATE TABLE IF NOT EXISTS tipo_documento (id_tipo_doc INTEGER PRIMARY KEY AUTOINCREMENT, nombre_tipo TEXT NOT NULL UNIQUE)",
        "CREATE TABLE IF NOT EXISTS estado_habitacion (id_estado INTEGER PRIMARY KEY AUTOINCREMENT, nombre_estado TEXT NOT NULL UNIQUE)",
        "CREATE TABLE IF NOT EXISTS metodo_pago (id_metodo INTEGER PRIMARY KEY AUTOINCREMENT, nombre_metodo TEXT NOT NULL UNIQUE)",
        "CREATE TABLE IF NOT EXISTS empleado (id_empleado INTEGER PRIMARY KEY AUTOINCREMENT, nombres TEXT NOT NULL, apellidos TEXT NOT NULL, dni TEXT NOT NULL UNIQUE, cargo TEXT NOT NULL, telefono TEXT, estado INTEGER NOT NULL DEFAULT 1)",
        "CREATE TABLE IF NOT EXISTS usuario (id_usuario INTEGER PRIMARY KEY AUTOINCREMENT, id_empleado INTEGER NOT NULL UNIQUE REFERENCES empleado(id_empleado), id_rol INTEGER NOT NULL REFERENCES rol(id_rol), usuario TEXT NOT NULL UNIQUE, contrasena TEXT NOT NULL, estado INTEGER NOT NULL DEFAULT 1)",
        "CREATE TABLE IF NOT EXISTS huesped (id_huesped INTEGER PRIMARY KEY AUTOINCREMENT, id_tipo_doc INTEGER NOT NULL REFERENCES tipo_documento(id_tipo_doc), numero_documento TEXT NOT NULL, nombres_apellidos TEXT NOT NULL, nacionalidad TEXT, estado_civil TEXT, lugar_nacimiento TEXT, fecha_nacimiento TEXT, telefono TEXT, UNIQUE (id_tipo_doc, numero_documento))",
        "CREATE TABLE IF NOT EXISTS habitacion (id_habitacion INTEGER PRIMARY KEY AUTOINCREMENT, id_estado INTEGER NOT NULL DEFAULT 1 REFERENCES estado_habitacion(id_estado), numero TEXT NOT NULL UNIQUE, tarifa REAL NOT NULL, descripcion TEXT)",
        "CREATE TABLE IF NOT EXISTS estadia (id_estadia INTEGER PRIMARY KEY AUTOINCREMENT, id_huesped INTEGER NOT NULL REFERENCES huesped(id_huesped), id_habitacion INTEGER NOT NULL REFERENCES habitacion(id_habitacion), id_empleado INTEGER NOT NULL REFERENCES empleado(id_empleado), fecha_ingreso TEXT NOT NULL, fecha_salida TEXT, fecha_salida_prevista TEXT, noches INTEGER NOT NULL DEFAULT 1, total_acordado REAL NOT NULL DEFAULT 0, modalidad_pago TEXT NOT NULL DEFAULT 'Pago único')",
        "CREATE TABLE IF NOT EXISTS pago (id_pago INTEGER PRIMARY KEY AUTOINCREMENT, id_estadia INTEGER NOT NULL REFERENCES estadia(id_estadia), id_metodo INTEGER NOT NULL REFERENCES metodo_pago(id_metodo), monto REAL NOT NULL, fecha_pago TEXT NOT NULL, numero_comprobante TEXT NOT NULL UNIQUE, estado TEXT NOT NULL DEFAULT 'Pagado' CHECK (estado IN ('Pagado', 'Anulado')), motivo_anulacion TEXT)"
    ];
    foreach ($schema as $sql) {
        db()->exec($sql);
    }
    $columns = array_column(q('PRAGMA table_info(estadia)'), 'name');
    $migrations = [
        'fecha_salida_prevista' => "ALTER TABLE estadia ADD COLUMN fecha_salida_prevista TEXT",
        'noches' => "ALTER TABLE estadia ADD COLUMN noches INTEGER NOT NULL DEFAULT 1",
        'total_acordado' => "ALTER TABLE estadia ADD COLUMN total_acordado REAL NOT NULL DEFAULT 0",
        'modalidad_pago' => "ALTER TABLE estadia ADD COLUMN modalidad_pago TEXT NOT NULL DEFAULT 'Pago único'"
    ];
    foreach ($migrations as $column => $sql) {
        if (!in_array($column, $columns, true)) {
            db()->exec($sql);
        }
    }
    db()->exec("UPDATE estadia SET noches=COALESCE(noches,1), total_acordado=CASE WHEN COALESCE(total_acordado,0)>0 THEN total_acordado ELSE (SELECT tarifa FROM habitacion h WHERE h.id_habitacion=estadia.id_habitacion) END, fecha_salida_prevista=COALESCE(fecha_salida_prevista, CASE WHEN fecha_salida IS NOT NULL THEN substr(fecha_salida,1,10) ELSE substr(fecha_ingreso,1,10) END), modalidad_pago=COALESCE(modalidad_pago,'Pago único')");
    if ((int)q1('SELECT COUNT(*) AS n FROM rol')['n'] === 0) {
        db()->beginTransaction();
        try {
            foreach ([ADMIN, RECEP, LIMP] as $name) run('INSERT INTO rol (nombre_rol) VALUES (?)', [$name]);
            foreach (['DNI', 'Carné de extranjería', 'Pasaporte'] as $name) run('INSERT INTO tipo_documento (nombre_tipo) VALUES (?)', [$name]);
            foreach (['Disponible', 'Ocupada', 'En limpieza'] as $name) run('INSERT INTO estado_habitacion (nombre_estado) VALUES (?)', [$name]);
            foreach (['Efectivo', 'Yape'] as $name) run('INSERT INTO metodo_pago (nombre_metodo) VALUES (?)', [$name]);
            for ($n = 1; $n <= 15; $n++) {
                run('INSERT INTO habitacion (numero, tarifa, descripcion, id_estado) VALUES (?,?,?,1)', [sprintf('%02d', $n), 50, 'Baño privado, agua caliente, cable, Wi-Fi e intercomunicador']);
            }
            run('INSERT INTO empleado (nombres, apellidos, dni, cargo, telefono) VALUES (?,?,?,?,?)', ['Delia', 'Villanueva García De Cotrina', '00000000', 'Propietaria', '']);
            run('INSERT INTO usuario (id_empleado,id_rol,usuario,contrasena) VALUES (1,1,?,?)', ['admin', password_hash('admin123', PASSWORD_DEFAULT)]);
            db()->commit();
        } catch (Throwable $e) {
            db()->rollBack();
            throw $e;
        }
    }
}

function flash(string $message, string $category = 'ok'): void
{
    $_SESSION['flashes'][] = [$category, $message];
}

function redirectTo(string $route, array $params = []): never
{
    header('Location: ' . url($route, $params));
    exit;
}

function back(string $fallback = 'panel'): never
{
    $ref = (string)($_SERVER['HTTP_REFERER'] ?? '');
    $base = basename((string)parse_url($ref, PHP_URL_PATH));
    if ($base === 'index.php' && str_contains($ref, (string)($_SERVER['HTTP_HOST'] ?? ''))) {
        header('Location: ' . $ref);
        exit;
    }
    redirectTo($fallback);
}

function requireLogin(): void
{
    if (empty($_SESSION['id_usuario'])) redirectTo('login');
}

function requireRoles(array $roles): void
{
    requireLogin();
    if (!in_array($_SESSION['rol'] ?? '', $roles, true)) {
        http_response_code(403);
        render('error.php', ['codigo' => 403, 'texto' => 'No tiene permiso para entrar a esta sección.']);
        exit;
    }
}

function money($value): string
{
    return 'S/ ' . number_format((float)$value, 2, '.', ',');
}

function fd(?string $value): string
{
    if (!$value) return '—';
    $time = strtotime(substr($value, 0, 10));
    return $time ? date('d/m/Y', $time) : $value;
}

function fdt(?string $value): string
{
    if (!$value) return '—';
    $time = strtotime($value);
    return $time ? date('d/m/Y H:i', $time) : $value;
}

function render(string $template, array $vars = [], bool $layout = true): void
{
    global $baseDir, $route;
    extract($vars, EXTR_SKIP);
    $templatePath = $baseDir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $template;
    ob_start();
    require $templatePath;
    $content = ob_get_clean();
    if ($layout) {
        require $baseDir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'base.php';
    } else {
        echo $content;
    }
}

function input(string $key, string $default = ''): string
{
    return trim((string)($_POST[$key] ?? $default));
}

function verifyPassword(string $plain, string $hash): bool
{
    if (password_verify($plain, $hash)) return true;
    if (preg_match('/^pbkdf2:sha256:(\d+)\$([^$]+)\$([a-f0-9]+)$/i', $hash, $m)) {
        $computed = hash_pbkdf2('sha256', $plain, $m[2], (int)$m[1], strlen($m[3]) / 2);
        return hash_equals(strtolower($m[3]), strtolower($computed));
    }
    // One-time compatibility for the default account hash in the bundled Flask database.
    $bundledLegacyHash = 'scrypt:32768:8:1$wtbiwvoCPXAfB1ua$8b75853ee3160c929e9600cd1b4f5e848fd43f9caed5b79d66ae92e7d7fcec0a65abe345ab327382ccdf1a7d2c2012638e7d8d2fb30e76c91f28997c5c457cec';
    if (hash_equals($bundledLegacyHash, $hash)) return hash_equals('admin123', $plain);
    return false;
}

function reportParams(): array
{
    $today = new DateTimeImmutable('today');
    $type = (string)($_GET['tipo'] ?? 'ocupacion');
    if (!in_array($type, ['ocupacion', 'ingresos', 'huespedes'], true)) $type = 'ocupacion';
    $from = (string)($_GET['desde'] ?? $today->modify('-6 days')->format('Y-m-d'));
    $to = (string)($_GET['hasta'] ?? $today->format('Y-m-d'));
    $d1 = DateTimeImmutable::createFromFormat('!Y-m-d', $from);
    $d2 = DateTimeImmutable::createFromFormat('!Y-m-d', $to);
    if (!$d1 || !$d2 || $d1 > $d2 || $d1->diff($d2)->days > 365) {
        $from = $today->modify('-6 days')->format('Y-m-d');
        $to = $today->format('Y-m-d');
    }
    return [$type, $from, $to, (int)($_GET['metodo'] ?? 0)];
}

function makeReport(string $type, string $from, string $to, int $method): array
{
    if ($type === 'ocupacion') {
        $total = max(1, (int)q1('SELECT COUNT(*) AS n FROM habitacion')['n']);
        $rows = [];
        $occupiedSum = 0;
        $start = new DateTimeImmutable($from);
        $end = new DateTimeImmutable($to);
        for ($d = $start; $d <= $end; $d = $d->modify('+1 day')) {
            $day = $d->format('Y-m-d');
            $occupied = (int)q1("SELECT COUNT(DISTINCT id_habitacion) AS n FROM estadia WHERE date(fecha_ingreso)<=? AND date(COALESCE(fecha_salida, date('now','localtime')))>=?", [$day, $day])['n'];
            $rows[] = [fd($day), $occupied, $total - $occupied, round($occupied * 100 / $total) . ' %'];
            $occupiedSum += $occupied * 100 / $total;
        }
        return [['Fecha', 'Hab. ocupadas', 'Hab. disponibles', '% de ocupación'], $rows, 'Promedio de ocupación del periodo: ' . (count($rows) ? round($occupiedSum / count($rows)) : 0) . ' %'];
    }
    if ($type === 'ingresos') {
        $sql = "SELECT p.fecha_pago,hu.nombres_apellidos,h.numero,m.nombre_metodo,p.numero_comprobante,p.monto FROM pago p JOIN estadia es ON es.id_estadia=p.id_estadia JOIN huesped hu ON hu.id_huesped=es.id_huesped JOIN habitacion h ON h.id_habitacion=es.id_habitacion JOIN metodo_pago m ON m.id_metodo=p.id_metodo WHERE p.estado='Pagado' AND date(p.fecha_pago) BETWEEN ? AND ?";
        $args = [$from, $to];
        if ($method) { $sql .= ' AND p.id_metodo=?'; $args[] = $method; }
        $data = q($sql . ' ORDER BY p.fecha_pago', $args);
        $rows = [];
        $sum = 0;
        foreach ($data as $r) {
            $sum += (float)$r['monto'];
            $rows[] = [fdt($r['fecha_pago']), $r['nombres_apellidos'], $r['numero'], $r['nombre_metodo'], $r['numero_comprobante'], money($r['monto'])];
        }
        return [['Fecha y hora', 'Huésped', 'Hab.', 'Método', 'N° comprobante', 'Monto'], $rows, 'Total de ingresos del periodo: ' . money($sum)];
    }
    $data = q("SELECT hu.nombres_apellidos,t.nombre_tipo||' '||hu.numero_documento AS doc,hu.nacionalidad,h.numero,es.fecha_ingreso,es.fecha_salida FROM estadia es JOIN huesped hu ON hu.id_huesped=es.id_huesped JOIN tipo_documento t ON t.id_tipo_doc=hu.id_tipo_doc JOIN habitacion h ON h.id_habitacion=es.id_habitacion WHERE date(es.fecha_ingreso) BETWEEN ? AND ? ORDER BY es.fecha_ingreso", [$from, $to]);
    $rows = [];
    foreach ($data as $r) $rows[] = [$r['nombres_apellidos'], $r['doc'], $r['nacionalidad'] ?: '—', $r['numero'], fdt($r['fecha_ingreso']), fdt($r['fecha_salida'])];
    return [['Huésped', 'Documento', 'Nacionalidad', 'Hab.', 'Ingreso', 'Salida'], $rows, 'Total de estadías en el periodo: ' . count($rows)];
}

try {
    initDb();
} catch (Throwable $e) {
    http_response_code(500);
    error_log('Error al inicializar Hostal Real: ' . $e->getMessage());
    echo 'No se pudo abrir la base de datos. Verifique que PHP tenga habilitada la extensión PDO SQLite y que la carpeta permita escritura.';
    exit;
}

$post = $_SERVER['REQUEST_METHOD'] === 'POST';
$adminOnly = ['empleados', 'empleados/guardar', 'empleados/eliminar', 'pagos/anular', 'reportes', 'reportes/exportar'];
$staff = ['huespedes', 'huespedes/guardar', 'huespedes/eliminar', 'estadias', 'estadias/nueva', 'estadias/guardar', 'estadias/salida', 'pagos', 'pagos/registrar', 'pagos/comprobante'];
if (in_array($route, $adminOnly, true)) requireRoles([ADMIN]);
elseif (in_array($route, $staff, true)) requireRoles([ADMIN, RECEP]);
elseif (!in_array($route, ['', 'index', 'login'], true)) requireLogin();

try {
    switch ($route) {
        case '':
        case 'index':
            redirectTo(empty($_SESSION['id_usuario']) ? 'login' : 'panel');
        case 'login':
            if (!empty($_SESSION['id_usuario'])) redirectTo('panel');
            if ($post) {
                $u = q1('SELECT u.*,r.nombre_rol,e.nombres,e.apellidos,e.estado AS emp_estado FROM usuario u JOIN rol r ON r.id_rol=u.id_rol JOIN empleado e ON e.id_empleado=u.id_empleado WHERE u.usuario=?', [input('usuario')]);
                if ($u && $u['estado'] && $u['emp_estado'] && verifyPassword(input('contrasena'), $u['contrasena'])) {
                    if (str_starts_with($u['contrasena'], 'pbkdf2:') || str_starts_with($u['contrasena'], 'scrypt:')) run('UPDATE usuario SET contrasena=? WHERE id_usuario=?', [password_hash(input('contrasena'), PASSWORD_DEFAULT), $u['id_usuario']]);
                    session_regenerate_id(true);
                    $_SESSION = ['id_usuario' => $u['id_usuario'], 'id_empleado' => $u['id_empleado'], 'rol' => $u['nombre_rol'], 'nombre' => $u['nombres'] . ' ' . $u['apellidos']];
                    redirectTo('panel');
                }
                flash('Usuario o contraseña incorrectos.', 'err');
            }
            render('login.php', [], false);
            break;
        case 'logout':
            $_SESSION = [];
            session_destroy();
            redirectTo('login');
        case 'panel':
            run('UPDATE habitacion SET id_estado=? WHERE id_estado=? AND NOT EXISTS (SELECT 1 FROM estadia es WHERE es.id_habitacion=habitacion.id_habitacion AND es.fecha_salida IS NULL)', [DISPONIBLE, OCUPADA]);
            $rooms = q("SELECT h.*,e.nombre_estado,es.id_estadia,es.fecha_ingreso,es.fecha_salida_prevista,es.noches,es.total_acordado,hu.nombres_apellidos AS huesped FROM habitacion h JOIN estado_habitacion e ON e.id_estado=h.id_estado LEFT JOIN estadia es ON es.id_habitacion=h.id_habitacion AND es.fecha_salida IS NULL LEFT JOIN huesped hu ON hu.id_huesped=es.id_huesped ORDER BY h.numero");
            $counts = [1 => 0, 2 => 0, 3 => 0];
            foreach ($rooms as $r) $counts[(int)$r['id_estado']] = ($counts[(int)$r['id_estado']] ?? 0) + 1;
            render('panel.php', ['habs' => $rooms, 'cont' => $counts]);
            break;
        case 'habitaciones':
            $text = trim((string)($_GET['q'] ?? ''));
            $state = (int)($_GET['estado'] ?? 0);
            $sql = 'SELECT h.*,e.nombre_estado FROM habitacion h JOIN estado_habitacion e ON e.id_estado=h.id_estado WHERE h.numero LIKE ?';
            $args = ['%' . $text . '%'];
            if ($state) { $sql .= ' AND h.id_estado=?'; $args[] = $state; }
            render('habitaciones.php', ['filas' => q($sql . ' ORDER BY h.numero', $args), 'estados' => q('SELECT * FROM estado_habitacion'), 'texto' => $text, 'estado' => $state]);
            break;
        case 'habitaciones/guardar':
            if (!$post || ($_SESSION['rol'] ?? '') !== ADMIN) { http_response_code(405); break; }
            $id = (int)input('id_habitacion');
            $number = input('numero'); $rate = (float)str_replace(',', '.', input('tarifa')); $desc = input('descripcion');
            $state = (int)input('id_estado', '1');
            if (!$number || $rate <= 0) flash('Ingrese el número de habitación y una tarifa válida.', 'err');
            else {
                try {
                    if ($id) {
                        $hasStay = q1('SELECT 1 FROM estadia WHERE id_habitacion=? AND fecha_salida IS NULL', [$id]);
                        if ($hasStay) $state = OCUPADA;
                        elseif ($state === OCUPADA) { flash('No se puede marcar como ocupada una habitación sin una estadía activa.', 'err'); redirectTo('habitaciones'); }
                        if (!q1('SELECT 1 FROM estado_habitacion WHERE id_estado=?', [$state])) $state = DISPONIBLE;
                        run('UPDATE habitacion SET numero=?,tarifa=?,descripcion=?,id_estado=? WHERE id_habitacion=?', [$number, $rate, $desc, $state, $id]);
                        flash('Habitación modificada correctamente.');
                    }
                    else {
                        if (!q1('SELECT 1 FROM estado_habitacion WHERE id_estado=?', [$state])) $state = DISPONIBLE;
                        if ($state === OCUPADA) $state = DISPONIBLE;
                        run('INSERT INTO habitacion (numero,tarifa,descripcion,id_estado) VALUES (?,?,?,?)', [$number, $rate, $desc, $state]);
                        flash('Habitación registrada correctamente.');
                    }
                } catch (PDOException $e) {
                    if (str_contains($e->getMessage(), 'UNIQUE')) flash('Ya existe una habitación con ese número.', 'err'); else throw $e;
                }
            }
            redirectTo('habitaciones');
        case 'habitaciones/disponible':
            if (!$post) { http_response_code(405); break; }
            $id = (int)($_GET['id'] ?? 0); $room = q1('SELECT * FROM habitacion WHERE id_habitacion=?', [$id]);
            if ($room && (int)$room['id_estado'] === EN_LIMPIEZA) { run('UPDATE habitacion SET id_estado=? WHERE id_habitacion=?', [DISPONIBLE, $id]); flash('Habitación ' . $room['numero'] . ' marcada como disponible.'); }
            else flash('Solo se puede liberar una habitación que está en limpieza.', 'err');
            back();
        case 'huespedes':
            $text = trim((string)($_GET['q'] ?? ''));
            render('huespedes.php', ['filas' => q('SELECT hu.*,t.nombre_tipo FROM huesped hu JOIN tipo_documento t ON t.id_tipo_doc=hu.id_tipo_doc WHERE hu.nombres_apellidos LIKE ? OR hu.numero_documento LIKE ? ORDER BY hu.nombres_apellidos', ['%' . $text . '%', '%' . $text . '%']), 'tipos' => q('SELECT * FROM tipo_documento'), 'texto' => $text, 'nuevo' => $_GET['nuevo'] ?? '']);
            break;
        case 'huespedes/guardar':
            if (!$post) { http_response_code(405); break; }
            $id = (int)input('id_huesped');
            $data = [(int)input('id_tipo_doc'), input('numero_documento'), input('nombres_apellidos'), input('nacionalidad'), input('estado_civil'), input('lugar_nacimiento'), input('fecha_nacimiento'), input('telefono')];
            if (!$data[0] || !$data[1] || !$data[2]) flash('Complete el tipo y número de documento y los nombres y apellidos.', 'err');
            else try {
                if ($id) { run('UPDATE huesped SET id_tipo_doc=?,numero_documento=?,nombres_apellidos=?,nacionalidad=?,estado_civil=?,lugar_nacimiento=?,fecha_nacimiento=?,telefono=? WHERE id_huesped=?', array_merge($data, [$id])); flash('Huésped modificado correctamente.'); }
                else { run('INSERT INTO huesped (id_tipo_doc,numero_documento,nombres_apellidos,nacionalidad,estado_civil,lugar_nacimiento,fecha_nacimiento,telefono) VALUES (?,?,?,?,?,?,?,?)', $data); flash('Huésped registrado correctamente.'); }
            } catch (PDOException $e) { if (str_contains($e->getMessage(), 'UNIQUE')) flash('Ya existe un huésped con ese documento.', 'err'); else throw $e; }
            redirectTo('huespedes');
        case 'huespedes/eliminar':
            if (!$post) { http_response_code(405); break; }
            $id = (int)($_GET['id'] ?? 0);
            if (q1('SELECT 1 FROM estadia WHERE id_huesped=?', [$id])) flash('No se puede eliminar: el huésped tiene estadías asociadas.', 'err');
            else { run('DELETE FROM huesped WHERE id_huesped=?', [$id]); flash('Huésped eliminado.'); }
            redirectTo('huespedes');
        case 'estadias':
            $tab = ($_GET['tab'] ?? 'activas') === 'historial' ? 'historial' : 'activas';
            $text = trim((string)($_GET['q'] ?? '')); $from = (string)($_GET['desde'] ?? ''); $to = (string)($_GET['hasta'] ?? '');
            $sql = "SELECT es.*,hu.nombres_apellidos,h.numero,p.estado AS pago_estado,m.nombre_metodo,p.id_pago FROM estadia es JOIN huesped hu ON hu.id_huesped=es.id_huesped JOIN habitacion h ON h.id_habitacion=es.id_habitacion LEFT JOIN pago p ON p.id_pago=(SELECT p2.id_pago FROM pago p2 WHERE p2.id_estadia=es.id_estadia ORDER BY p2.id_pago DESC LIMIT 1) LEFT JOIN metodo_pago m ON m.id_metodo=p.id_metodo WHERE (hu.nombres_apellidos LIKE ? OR h.numero LIKE ?)";
            $args = ['%' . $text . '%', '%' . $text . '%'];
            if ($tab === 'activas') $sql .= ' AND es.fecha_salida IS NULL';
            else { if ($from) { $sql .= ' AND date(es.fecha_ingreso)>=?'; $args[] = $from; } if ($to) { $sql .= ' AND date(es.fecha_ingreso)<=?'; $args[] = $to; } }
            render('estadias.php', ['filas' => q($sql . ' ORDER BY es.fecha_ingreso DESC', $args), 'tab' => $tab, 'texto' => $text, 'desde' => $from, 'hasta' => $to, 'n_act' => (int)q1('SELECT COUNT(*) AS n FROM estadia WHERE fecha_salida IS NULL')['n']]);
            break;
        case 'estadias/nueva':
            render('estadias_nueva.php', ['disp' => q('SELECT * FROM habitacion WHERE id_estado=? ORDER BY numero', [DISPONIBLE]), 'hus' => q('SELECT hu.numero_documento,hu.nombres_apellidos,t.nombre_tipo FROM huesped hu JOIN tipo_documento t ON t.id_tipo_doc=hu.id_tipo_doc ORDER BY hu.nombres_apellidos'), 'metodos' => q('SELECT * FROM metodo_pago'), 'sel' => (int)($_GET['hab'] ?? 0)]);
            break;
        case 'estadias/guardar':
            if (!$post) { http_response_code(405); break; }
            $roomId = (int)input('id_habitacion');
            $doc = trim(explode(' - ', input('huesped'))[0]);
            $guest = q1('SELECT * FROM huesped WHERE numero_documento=?', [$doc]);
            $room = q1('SELECT * FROM habitacion WHERE id_habitacion=? AND id_estado=?', [$roomId, DISPONIBLE]);
            $method = (int)input('id_metodo'); $nights = (int)input('noches'); $modal = input('modalidad_pago', 'Pago único');
            if (!$guest || !$room || !q1('SELECT 1 FROM metodo_pago WHERE id_metodo=?', [$method])) { flash('Seleccione un huésped registrado, una habitación disponible y el método de pago.', 'err'); redirectTo('estadias/nueva', ['hab' => $roomId]); }
            if ($nights < 1 || $nights > 365) { flash('La estadía debe ser de 1 a 365 noches.', 'err'); redirectTo('estadias/nueva', ['hab' => $roomId]); }
            if (!in_array($modal, ['Pago único', 'Pagos separados'], true)) $modal = 'Pago único';
            $total = round((float)$room['tarifa'] * $nights, 2);
            $amount = (float)str_replace(',', '.', input('monto_inicial'));
            if ($modal === 'Pago único') {
                if ($amount < $total) { flash('El monto recibido debe cubrir el total acordado.', 'err'); redirectTo('estadias/nueva', ['hab' => $roomId]); }
                $amount = $total;
            } else {
                if ($amount <= 0 || $amount >= $total) { flash('El primer pago debe ser mayor que cero y menor que el total.', 'err'); redirectTo('estadias/nueva', ['hab' => $roomId]); }
            }
            db()->beginTransaction();
            try {
                $room = q1('SELECT * FROM habitacion WHERE id_habitacion=? AND id_estado=?', [$roomId, DISPONIBLE]);
                if (!$room) throw new RuntimeException('La habitación ya no está disponible.');
                $now = date('Y-m-d H:i');
                run('INSERT INTO estadia (id_huesped,id_habitacion,id_empleado,fecha_ingreso,fecha_salida_prevista,noches,total_acordado,modalidad_pago) VALUES (?,?,?,?,?,?,?,?)', [$guest['id_huesped'], $room['id_habitacion'], $_SESSION['id_empleado'], $now, date('Y-m-d', strtotime('+' . $nights . ' days')), $nights, $total, $modal]);
                $stayId = (int)db()->lastInsertId();
                run('INSERT INTO pago (id_estadia,id_metodo,monto,fecha_pago,numero_comprobante) VALUES (?,?,?,?,?)', [$stayId, $method, $amount, $now, 'TMP-' . $stayId]);
                $paymentId = (int)db()->lastInsertId();
                run('UPDATE pago SET numero_comprobante=? WHERE id_pago=?', [sprintf('0001-%06d', $paymentId), $paymentId]);
                run('UPDATE habitacion SET id_estado=? WHERE id_habitacion=?', [OCUPADA, $room['id_habitacion']]);
                db()->commit();
                flash('Ingreso registrado correctamente.');
                redirectTo('pagos/comprobante', ['id' => $paymentId]);
            } catch (Throwable $e) { db()->rollBack(); throw $e; }
        case 'estadias/salida':
            if (!$post) { http_response_code(405); break; }
            $id = (int)($_GET['id'] ?? 0); $stay = q1('SELECT * FROM estadia WHERE id_estadia=? AND fecha_salida IS NULL', [$id]);
            if (!$stay) flash('La estadía no existe o ya fue cerrada.', 'err');
            else {
                db()->beginTransaction();
                run('UPDATE estadia SET fecha_salida=? WHERE id_estadia=?', [date('Y-m-d H:i'), $id]);
                run('UPDATE habitacion SET id_estado=? WHERE id_habitacion=?', [EN_LIMPIEZA, $stay['id_habitacion']]);
                db()->commit();
                flash('Salida registrada. La habitación pasó a limpieza.');
            }
            back('estadias');
        case 'pagos':
            $text = trim((string)($_GET['q'] ?? '')); $from = (string)($_GET['desde'] ?? ''); $to = (string)($_GET['hasta'] ?? ''); $method = (int)($_GET['metodo'] ?? 0);
            $sql = 'SELECT p.*,hu.nombres_apellidos,h.numero,m.nombre_metodo FROM pago p JOIN estadia es ON es.id_estadia=p.id_estadia JOIN huesped hu ON hu.id_huesped=es.id_huesped JOIN habitacion h ON h.id_habitacion=es.id_habitacion JOIN metodo_pago m ON m.id_metodo=p.id_metodo WHERE (hu.nombres_apellidos LIKE ? OR p.numero_comprobante LIKE ?)';
            $args = ['%' . $text . '%', '%' . $text . '%'];
            if ($from) { $sql .= ' AND date(p.fecha_pago)>=?'; $args[] = $from; }
            if ($to) { $sql .= ' AND date(p.fecha_pago)<=?'; $args[] = $to; }
            if ($method) { $sql .= ' AND p.id_metodo=?'; $args[] = $method; }
            $rows = q($sql . ' ORDER BY p.fecha_pago DESC', $args); $sum = 0;
            foreach ($rows as $r) if ($r['estado'] === 'Pagado') $sum += (float)$r['monto'];
            $pending = q("SELECT es.id_estadia,hu.nombres_apellidos,h.numero,es.total_acordado,COALESCE((SELECT SUM(p2.monto) FROM pago p2 WHERE p2.id_estadia=es.id_estadia AND p2.estado='Pagado'),0) AS pagado FROM estadia es JOIN huesped hu ON hu.id_huesped=es.id_huesped JOIN habitacion h ON h.id_habitacion=es.id_habitacion WHERE es.fecha_salida IS NULL ORDER BY h.numero");
            foreach ($pending as &$p) $p['saldo'] = max(round((float)$p['total_acordado'] - (float)$p['pagado'], 2), 0);
            unset($p);
            $pending = array_values(array_filter($pending, fn($p) => $p['saldo'] > 0));
            render('pagos.php', ['filas' => $rows, 'total' => $sum, 'texto' => $text, 'desde' => $from, 'hasta' => $to, 'metodo' => $method, 'metodos' => q('SELECT * FROM metodo_pago'), 'pendientes' => $pending]);
            break;
        case 'pagos/registrar':
            if (!$post) { http_response_code(405); break; }
            $stayId = (int)input('id_estadia'); $method = (int)input('id_metodo'); $amount = (float)str_replace(',', '.', input('monto'));
            $stay = q1('SELECT * FROM estadia WHERE id_estadia=? AND fecha_salida IS NULL', [$stayId]);
            if (!q1('SELECT 1 FROM metodo_pago WHERE id_metodo=?', [$method])) flash('Seleccione un método de pago válido.', 'err');
            elseif (!$stay) flash('La estadía no está activa.', 'err');
            else {
                $paid = (float)q1("SELECT COALESCE(SUM(monto),0) AS n FROM pago WHERE id_estadia=? AND estado='Pagado'", [$stayId])['n'];
                $balance = max(round((float)$stay['total_acordado'] - $paid, 2), 0);
                if ($amount <= 0 || $amount > $balance) flash('El monto debe estar entre S/ 0.01 y el saldo de ' . money($balance) . '.', 'err');
                else {
                    run('INSERT INTO pago (id_estadia,id_metodo,monto,fecha_pago,numero_comprobante) VALUES (?,?,?,?,?)', [$stayId, $method, $amount, date('Y-m-d H:i'), 'TMP-' . $stayId . '-' . time()]);
                    $id = (int)db()->lastInsertId();
                    run('UPDATE pago SET numero_comprobante=? WHERE id_pago=?', [sprintf('0001-%06d', $id), $id]);
                    flash('Pago adicional registrado correctamente.');
                    redirectTo('pagos/comprobante', ['id' => $id]);
                }
            }
            redirectTo('pagos');
        case 'pagos/comprobante':
            $p = q1("SELECT p.*,hu.nombres_apellidos,hu.numero_documento,t.nombre_tipo,h.numero,es.fecha_ingreso,es.fecha_salida_prevista,es.noches,es.total_acordado,es.modalidad_pago,m.nombre_metodo,e.nombres||' '||e.apellidos AS atendio FROM pago p JOIN estadia es ON es.id_estadia=p.id_estadia JOIN huesped hu ON hu.id_huesped=es.id_huesped JOIN tipo_documento t ON t.id_tipo_doc=hu.id_tipo_doc JOIN habitacion h ON h.id_habitacion=es.id_habitacion JOIN metodo_pago m ON m.id_metodo=p.id_metodo JOIN empleado e ON e.id_empleado=es.id_empleado WHERE p.id_pago=?", [(int)($_GET['id'] ?? 0)]);
            if (!$p) { http_response_code(404); render('error.php', ['codigo' => 404, 'texto' => 'La página no existe.']); break; }
            $paid = (float)q1("SELECT COALESCE(SUM(monto),0) AS n FROM pago WHERE id_estadia=? AND estado='Pagado'", [$p['id_estadia']])['n'];
            render('comprobante.php', ['p' => $p, 'pagado' => $paid, 'saldo' => max((float)$p['total_acordado'] - $paid, 0)], false);
            break;
        case 'pagos/anular':
            if (!$post) { http_response_code(405); break; }
            $reason = input('motivo'); $id = (int)($_GET['id'] ?? 0);
            if (!$reason) flash('Escriba el motivo de la anulación.', 'err');
            elseif (!q1("SELECT id_pago FROM pago WHERE id_pago=? AND estado='Pagado'", [$id])) flash('El pago no existe o ya fue anulado.', 'err');
            else { run("UPDATE pago SET estado='Anulado',motivo_anulacion=? WHERE id_pago=? AND estado='Pagado'", [$reason, $id]); flash('Pago anulado correctamente.'); }
            redirectTo('pagos');
        case 'empleados':
            $text = trim((string)($_GET['q'] ?? ''));
            render('empleados.php', ['filas' => q('SELECT e.*,u.id_usuario,u.usuario,u.id_rol,r.nombre_rol,u.estado AS u_estado FROM empleado e JOIN usuario u ON u.id_empleado=e.id_empleado JOIN rol r ON r.id_rol=u.id_rol WHERE e.nombres LIKE ? OR e.apellidos LIKE ? OR e.dni LIKE ? ORDER BY e.apellidos', array_fill(0, 3, '%' . $text . '%')), 'roles' => q('SELECT * FROM rol'), 'texto' => $text]);
            break;
        case 'empleados/guardar':
            if (!$post) { http_response_code(405); break; }
            $id = (int)input('id_empleado'); $name = input('nombres'); $surname = input('apellidos'); $dni = input('dni'); $job = input('cargo'); $phone = input('telefono'); $user = input('usuario'); $role = (int)input('id_rol'); $password = input('contrasena'); $active = isset($_POST['estado']) ? 1 : 0;
            $isNew = !$id;
            if (!$name || !$surname || !$dni || !$job || !$user || !$role || (!$id && strlen($password) < 6) || ($id && $password !== '' && strlen($password) < 6)) flash('Complete todos los campos. La contraseña debe tener al menos 6 caracteres.', 'err');
            else {
                db()->beginTransaction();
                try {
                    if ($id) {
                        run('UPDATE empleado SET nombres=?,apellidos=?,dni=?,cargo=?,telefono=?,estado=? WHERE id_empleado=?', [$name, $surname, $dni, $job, $phone, $active, $id]);
                        run('UPDATE usuario SET usuario=?,id_rol=?,estado=? WHERE id_empleado=?', [$user, $role, $active, $id]);
                        if ($password) run('UPDATE usuario SET contrasena=? WHERE id_empleado=?', [password_hash($password, PASSWORD_DEFAULT), $id]);
                    } else {
                        run('INSERT INTO empleado (nombres,apellidos,dni,cargo,telefono,estado) VALUES (?,?,?,?,?,?)', [$name, $surname, $dni, $job, $phone, $active]);
                        $id = (int)db()->lastInsertId();
                        run('INSERT INTO usuario (id_empleado,id_rol,usuario,contrasena,estado) VALUES (?,?,?,?,?)', [$id, $role, $user, password_hash($password, PASSWORD_DEFAULT), $active]);
                    }
                    db()->commit(); flash($isNew ? 'Empleado y usuario registrados correctamente.' : 'Empleado modificado correctamente.');
                } catch (PDOException $e) {
                    db()->rollBack();
                    if (str_contains($e->getMessage(), 'UNIQUE')) flash('El DNI o el nombre de usuario ya están registrados.', 'err'); else throw $e;
                }
            }
            redirectTo('empleados');
        case 'empleados/eliminar':
            if (!$post) { http_response_code(405); break; }
            $id = (int)($_GET['id'] ?? 0);
            if ($id === (int)$_SESSION['id_empleado']) flash('No puede eliminar su propia cuenta.', 'err');
            elseif (q1('SELECT 1 FROM estadia WHERE id_empleado=?', [$id])) flash('No se puede eliminar: el empleado tiene estadías registradas. Desactívelo en su lugar.', 'err');
            else { run('DELETE FROM usuario WHERE id_empleado=?', [$id]); run('DELETE FROM empleado WHERE id_empleado=?', [$id]); flash('Empleado eliminado.'); }
            redirectTo('empleados');
        case 'reportes':
            [$type, $from, $to, $method] = reportParams();
            [$cols, $rows, $summary] = !empty($_GET['generar']) ? makeReport($type, $from, $to, $method) : [[], [], ''];
            render('reportes.php', ['tipo' => $type, 'desde' => $from, 'hasta' => $to, 'metodo' => $method, 'metodos' => q('SELECT * FROM metodo_pago'), 'cols' => $cols, 'filas' => $rows, 'resumen' => $summary, 'generado' => !empty($_GET['generar'])]);
            break;
        case 'reportes/exportar':
            [$type, $from, $to, $method] = reportParams();
            [$cols, $rows, $summary] = makeReport($type, $from, $to, $method);
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="reporte_' . $type . '_' . $from . '_' . $to . '.csv"');
            echo "\xEF\xBB\xBF";
            $out = fopen('php://output', 'w');
            fputcsv($out, ['HOSTAL REAL - Reporte de ' . $type, fd($from) . ' al ' . fd($to)], ';');
            fputcsv($out, $cols, ';');
            foreach ($rows as $row) fputcsv($out, $row, ';');
            fputcsv($out, [$summary], ';');
            fclose($out);
            break;
        default:
            http_response_code(404);
            render('error.php', ['codigo' => 404, 'texto' => 'La página no existe.']);
    }
} catch (Throwable $e) {
    if (db()->inTransaction()) db()->rollBack();
    error_log('Error en ruta ' . $route . ': ' . $e->getMessage());
    http_response_code(500);
    render('error.php', ['codigo' => 500, 'texto' => 'Ocurrió un error interno. Intente regresar al Inicio.']);
}
