<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
try { require ROOT.'/app/controller.php'; }
catch (Throwable $exception) {
    $reference=bin2hex(random_bytes(4));
    // Pesan driver SQL dapat berisi data pribadi; log hanya kelas/kode/lokasi.
    error_log('Incident '.$reference.' '.get_class($exception).' code='.$exception->getCode().' at '.basename($exception->getFile()).':'.$exception->getLine());
    http_response_code(500);
    $errorCode=500; $incident=$reference; $user=null;
    require ROOT.'/views/error.php';
}
