<?php
declare(strict_types=1);
function e(mixed $v): string { return htmlspecialchars(is_scalar($v)?(string)$v:'', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function mdi(string $name, string $extraClass = ''): string {
    static $paths = [];
    if (!preg_match('/^[a-z0-9-]+$/D', $name)) throw new InvalidArgumentException('Nama ikon MDI tidak valid.');
    if (!isset($paths[$name])) {
        $file = ROOT.'/public/assets/icons/mdi/'.$name.'.svg';
        if (!is_file($file) || !preg_match('/<path\b[^>]*\bd="([^"]+)"/', file_get_contents($file), $match)) {
            throw new RuntimeException('Aset ikon MDI tidak ditemukan: '.$name);
        }
        $paths[$name] = $match[1];
    }
    return '<svg class="mdi-icon'.($extraClass!==''?' '.e($extraClass):'').'" data-mdi="'.e($name).'" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="'.e($paths[$name]).'"></path></svg>';
}
function brand(): array { static $b; return $b ??= require ROOT.'/config/branding.php'; }
function url(string $page = 'dashboard', array $params = []): string { return 'index.php?'.http_build_query(['page'=>$page]+$params); }
function redirect(string $page, array $params = []): never { header('Location: '.url($page,$params), true, 303); exit; }
function csrf(): string { return '<input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">'; }
function token(): string { return bin2hex(random_bytes(32)); }
function money(mixed $value): string {
    if ($value === null || $value === '') return '-';
    return 'Rp'.number_format((float)$value, fmod((float)$value,1.0) == 0 ? 0 : 2, ',', '.');
}
function human_date(?string $value): string { return $value ? date('d/m/Y',strtotime($value)) : '-'; }
function badge(string $status): string {
    $class = match($status) { 'Selesai','Sudah Diambil'=>'green','Dalam Pengerjaan'=>'blue','Dibatalkan'=>'red','Menunggu Sparepart'=>'purple',default=>'amber' };
    return '<span class="badge '.$class.'">'.e($status).'</span>';
}
function current_user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    $user=Database::one('SELECT id,phone,role,password_hash FROM users WHERE id=?',[$_SESSION['user_id']]);
    if (!$user || !isset($_SESSION['auth_stamp']) || !hash_equals(Auth::stamp($user),(string)$_SESSION['auth_stamp'])) { $_SESSION=['csrf'=>token(),'last_seen'=>time()]; session_regenerate_id(true); return null; }
    unset($user['password_hash']); return $user;
}
function sign_in(array $user): void { session_regenerate_id(true); $_SESSION=['user_id'=>$user['id'],'auth_stamp'=>Auth::stamp($user),'csrf'=>token(),'last_seen'=>time()]; }
function require_login(): array { $u=current_user(); if (!$u) redirect('login'); return $u; }
function require_admin(): array { $u=require_login(); if ($u['role']!=='admin') abort_page(403); return $u; }
function abort_page(int $status): never {
    http_response_code($status);
    // Apache needs an explicit reason phrase for this non-standard expiry code.
    if ($status===419) header('HTTP/1.1 419 Page Expired', true, 419);
    $title=(string)$status; $user=current_user(); $errorCode=$status;
    require ROOT.'/views/error.php'; exit;
}
function flash(string $message): void { $_SESSION['flash']=$message; }
function render(string $view, array $data=[]): void { extract($data); $user=current_user(); require ROOT.'/views/'.$view.'.php'; }
function request_value(array $data, string $name, mixed $default=''): mixed { return $data[$name]??$default; }
function input_field(string $name,string $label,array $data=[],string $type='text',bool $required=false,string $extra=''): void {
    echo '<label class="field"><span>'.e($label).($required?' <b>*</b>':'').'</span><input type="'.e($type).'" name="'.e($name).'" value="'.e($data[$name]??'').'" '.($required?'required ':'').$extra.'></label>';
}
function textarea_field(string $name,string $label,array $data=[],bool $required=false): void {
    echo '<label class="field full"><span>'.e($label).($required?' <b>*</b>':'').'</span><textarea name="'.e($name).'" rows="3" maxlength="4000" '.($required?'required':'').'>'.e($data[$name]??'').'</textarea></label>';
}
function pagination(int $page,int $count,string $route,array $params=[]): void {
    if ($page===1 && $count<20) return;
    echo '<nav class="pagination" aria-label="Halaman data">';
    if ($page>1) echo '<a class="button secondary" href="'.e(url($route,$params+['p'=>$page-1])).'">'.mdi('arrow-left').' Sebelumnya</a>';
    echo '<span>Halaman '.e($page).'</span>';
    if ($count===20) echo '<a class="button secondary" href="'.e(url($route,$params+['p'=>$page+1])).'">Berikutnya '.mdi('arrow-right').'</a>';
    echo '</nav>';
}
