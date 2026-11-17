<?php
// Router PHP development: hanya aset publik yang boleh dilayani langsung.
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if (in_array($path,['/admin','/admin/','/admin/index.php'],true)) {
    $_SERVER['SCRIPT_NAME']='/admin/index.php';
    require __DIR__.'/admin/index.php'; return true;
}
if (preg_match('#^/assets/[a-zA-Z0-9_./-]+$#D',$path) && !str_contains($path,'..') && is_file(__DIR__.$path)) return false;
if (!in_array($path,['/','/index.php'],true)) { http_response_code(404); echo '404 — Halaman tidak ditemukan'; return true; }
require __DIR__.'/index.php';
