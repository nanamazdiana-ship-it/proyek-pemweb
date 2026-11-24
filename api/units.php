<?php
declare(strict_types=1);

// Alias kompatibilitas; seluruh CRUD unit ditangani router API utama.
$unitId = $_GET['id'] ?? null;
$unitRoute = 'units';

if (isset($_GET['id'])) {
    $unitRoute .= '/' . rawurlencode(
        is_scalar($unitId) ? (string) $unitId : ''
    );
}

$_GET['r'] = $unitRoute;
require __DIR__ . '/index.php';