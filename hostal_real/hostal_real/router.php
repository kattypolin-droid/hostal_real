<?php
$requestPath = rawurldecode((string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/'));
$segments = explode('/', str_replace('\\', '/', $requestPath));

foreach ($segments as $segment) {
    if ($segment === '.' || $segment === '..' || ($segment !== '' && $segment[0] === '.')) {
        http_response_code(404);
        exit;
    }
}

if (preg_match('~(?:^|/)templates(?:/|$)~i', $requestPath)
    || preg_match('~(?:^|/)hostal_real\.db(?:-(?:journal|wal|shm))?$~i', $requestPath)) {
    http_response_code(404);
    exit;
}

return false;
