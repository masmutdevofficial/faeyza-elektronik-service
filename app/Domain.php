<?php
declare(strict_types=1);
final class ValidationException extends RuntimeException {}
final class Clock {
    public static function today(): string {
        $test=getenv('TEST_DATE');
        return getenv('APP_ENV')==='testing' && $test && preg_match('/^\d{4}-\d{2}-\d{2}$/D',$test) ? $test : date('Y-m-d');
    }
}
final class Validate {
    public static function password(array $d,string $key='password',int $minimum=0): string {
        $v=$d[$key]??null;
        if(!is_string($v) || !mb_check_encoding($v,'UTF-8') || mb_strlen($v)<$minimum || strlen($v)>72 || str_contains($v,"\0")) throw new ValidationException('Password minimal '.$minimum.' karakter, maksimal 72 byte, dan tidak mengandung karakter null.');
        return $v;
    }
    public static function text(array $d,string $key,int $max=4000,bool $required=true): string {
        $v=$d[$key]??'';
        if (!is_string($v) && !is_numeric($v)) throw new ValidationException('Format '.$key.' tidak valid.');
        $v=trim((string)$v);
        if (($required && $v==='') || mb_strlen($v)>$max) throw new ValidationException('Kolom '.$key.' wajib diisi dan maksimal '.$max.' karakter.');
        return $v;
    }
    public static function phone(mixed $value): string {
        if (!is_string($value)) throw new ValidationException('Nomor HP tidak valid.');
        $v=preg_replace('/[\s().-]/','',trim($value));
        if (str_starts_with($v,'+')) $v=substr($v,1);
        if (str_starts_with($v,'0')) $v='62'.substr($v,1);
        if (!preg_match('/^628[1-9][0-9]{6,10}$/D',$v)) throw new ValidationException('Gunakan nomor HP Indonesia yang valid, misalnya 08170002466.');
        return $v;
    }
    public static function integer(mixed $v,int $min=0,int $max=1000000000): int {
        if ((!is_string($v) && !is_int($v)) || !preg_match('/^\d{1,10}$/D',(string)$v) || (int)$v<$min || (int)$v>$max) throw new ValidationException('Jumlah harus bilangan bulat antara '.$min.' dan '.$max.'.');
        return (int)$v;
    }
    public static function decimal(mixed $v): ?string {
        if ($v===null || $v==='') return null;
        if (!is_scalar($v) || !preg_match('/^\d{1,12}(\.\d{1,2})?$/D',(string)$v)) throw new ValidationException('Biaya harus angka positif atau nol, maksimal 12 digit dan 2 desimal.');
        return (string)$v;
    }
    public static function date(mixed $v,bool $required=false): ?string {
        if (($v===null || $v==='') && !$required) return null;
        if (!is_string($v)) throw new ValidationException('Tanggal tidak valid.');
        $date=DateTimeImmutable::createFromFormat('!Y-m-d',$v);
        if (!$date || $date->format('Y-m-d')!==$v || $v<'1900-01-01' || $v>'2199-12-31') throw new ValidationException('Tanggal tidak valid.');
        return $v;
    }
    public static function key(mixed $v): string {
        if (!is_string($v) || !preg_match('/^[a-f0-9]{64}$/D',$v)) throw new ValidationException('Token pengiriman tidak valid. Buka kembali formulir.');
        return $v;
    }
}
final class Auth {
    public const PASSWORD_MIN=6;
    public static function stamp(array $user): string { $version=(int)($user['session_version']??1); return hash('sha256',$user['id'].'|'.$user['phone'].'|'.$user['role'].'|'.$user['password_hash'].($version>1?'|session:'.$version:'')); }
    public static function register(array $d): int {
        $phone=Validate::phone($d['phone']??''); $name=Validate::text($d,'name',150);
        $address=Validate::text($d,'address'); $password=Validate::password($d,'password',self::PASSWORD_MIN);
        try { return Database::tx(function() use($phone,$name,$address,$password) {
            Database::query('INSERT INTO users(phone,password_hash,role) VALUES (?,?,?)',[$phone,password_hash($password,PASSWORD_DEFAULT),'customer']);
            $id=Database::id(); Database::query('INSERT INTO customers(user_id,name,address) VALUES (?,?,?)',[$id,$name,$address]); return $id;
        }); } catch(mysqli_sql_exception $e) { if ($e->getCode()===1062) throw new ValidationException('Nomor HP sudah terdaftar. Hubungi bengkel jika perlu pemulihan akun.'); throw $e; }
    }
    public static function login(string $phone,string $password,string $ip): array {
        // Dua bucket membatasi percobaan per nomor dan per IP, termasuk akun yang belum ada.
        try { $normalized=Validate::phone($phone); } catch(ValidationException) { $normalized='invalid'; }
        $keys=[hash('sha256','phone:'.$normalized),hash('sha256','ip:'.$ip)]; sort($keys);
        $allowed=Database::tx(function() use($keys) {
            $rows=[];
            foreach ($keys as $key) {
                Database::query('INSERT IGNORE INTO login_limits(bucket,attempts,window_started) VALUES (?,0,NOW())',[$key]);
                $r=Database::one('SELECT *,TIMESTAMPDIFF(SECOND,window_started,NOW()) age FROM login_limits WHERE bucket=? FOR UPDATE',[$key]);
                if ((int)$r['age']>=900) { Database::query('UPDATE login_limits SET attempts=0,window_started=NOW() WHERE bucket=?',[$key]); $r['attempts']=0; }
                $rows[$key]=$r;
            }
            foreach ($rows as $r) if ((int)$r['attempts']>=8) return false;
            foreach ($keys as $key) Database::query('UPDATE login_limits SET attempts=attempts+1 WHERE bucket=?',[$key]);
            return true;
        });
        if (!$allowed) throw new ValidationException('Terlalu banyak percobaan login. Coba kembali dalam 15 menit.');
        $u=Database::one('SELECT u.*,c.deleted_at customer_deleted_at FROM users u LEFT JOIN customers c ON c.user_id=u.id WHERE u.phone=?',[$normalized]);
        // Hash dummy menjaga proses verifikasi tetap dilakukan untuk nomor yang tidak terdaftar.
        $hash=$u['password_hash']??'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        if (!password_verify($password,$hash) || !$u || ($u['role']==='customer' && $u['customer_deleted_at']!==null)) throw new ValidationException('Nomor HP atau password salah.');
        if (password_needs_rehash($hash,PASSWORD_DEFAULT)) {
            $newHash=password_hash($password,PASSWORD_DEFAULT);
            if (Database::query('UPDATE users SET password_hash=? WHERE id=? AND password_hash=?',[$newHash,$u['id'],$hash])->affected_rows!==1) throw new ValidationException('Akun berubah. Silakan masuk kembali.');
            $u['password_hash']=$newHash;
        }
        // Bucket IP tetap menghitung percobaan agar login sukses tidak mereset proteksi brute force IP.
        Database::query('UPDATE login_limits SET attempts=0 WHERE bucket=?',[hash('sha256','phone:'.$normalized)]);
        return $u;
    }
    public static function admin(array $actor): void { if (($actor['role']??'')!=='admin') throw new ValidationException('Akses admin diperlukan.'); }
}
final class Accounts {
    private static function verifyCurrent(array $actor,array $d): array {
        $user=Database::one('SELECT * FROM users WHERE id=?',[$actor['id']]);
        if (!$user) throw new ValidationException('Akun tidak ditemukan.');
        return Auth::login($user['phone'],Validate::password($d,'current_password',1),$_SERVER['REMOTE_ADDR']??'account-change');
    }
    private static function newPassword(array $d): string {
        $password=Validate::password($d,'new_password',Auth::PASSWORD_MIN);
        if ($password!==Validate::password($d,'password_confirm',Auth::PASSWORD_MIN)) throw new ValidationException('Konfirmasi password tidak sama.');
        return $password;
    }
    public static function changePassword(array $d,array $actor): array {
        $password=self::newPassword($d); $verified=self::verifyCurrent($actor,$d);
        return Database::tx(function() use($verified,$password) {
            $user=Database::one('SELECT * FROM users WHERE id=? FOR UPDATE',[$verified['id']]);
            if (!$user || !hash_equals($verified['password_hash'],$user['password_hash'])) throw new ValidationException('Akun berubah. Silakan masuk kembali.');
            $user['password_hash']=password_hash($password,PASSWORD_DEFAULT);
            Database::query('UPDATE users SET password_hash=? WHERE id=?',[$user['password_hash'],$user['id']]);
            return $user;
        });
    }
    public static function changePhone(array $d,array $actor): array {
        Auth::admin($actor); $phone=Validate::phone($d['phone']??''); $verified=self::verifyCurrent($actor,$d);
        try { return Database::tx(function() use($verified,$phone) {
            $user=Database::one('SELECT * FROM users WHERE id=? FOR UPDATE',[$verified['id']]);
            if (!$user || $user['role']!=='admin' || !hash_equals($verified['password_hash'],$user['password_hash'])) throw new ValidationException('Akun berubah. Silakan masuk kembali.');
            Database::query('UPDATE users SET phone=? WHERE id=?',[$phone,$user['id']]);
            $user['phone']=$phone; return $user;
        }); } catch(mysqli_sql_exception $e) { if ($e->getCode()===1062) throw new ValidationException('Nomor HP sudah digunakan akun lain.'); throw $e; }
    }
    public static function resetCustomerPassword(int $customerId,array $d,array $actor): void {
        Auth::admin($actor); $password=self::newPassword($d); $verified=self::verifyCurrent($actor,$d);
        Database::tx(function() use($customerId,$verified,$password) {
            $admin=Database::one('SELECT * FROM users WHERE id=? FOR UPDATE',[$verified['id']]);
            if (!$admin || $admin['role']!=='admin' || !hash_equals($verified['password_hash'],$admin['password_hash'])) throw new ValidationException('Akun admin berubah. Silakan masuk kembali.');
            $customer=Database::one("SELECT u.id FROM customers c JOIN users u ON u.id=c.user_id WHERE c.id=? AND u.role='customer' AND c.deleted_at IS NULL FOR UPDATE",[$customerId]);
            if (!$customer) throw new ValidationException('Konsumen tidak ditemukan.');
            Database::query('UPDATE users SET password_hash=? WHERE id=?',[password_hash($password,PASSWORD_DEFAULT),$customer['id']]);
        });
    }
}
final class Orders {
    public const COMPLETED_STATUSES=['Selesai','Sudah Diambil'];
    public const STATUS_GROUPS=['working'=>['Menunggu Konfirmasi','Diterima','Dalam Pengerjaan','Menunggu Sparepart'],'done'=>self::COMPLETED_STATUSES];
    public const STATUSES=['Menunggu Konfirmasi','Diterima','Dalam Pengerjaan','Menunggu Sparepart','Selesai','Sudah Diambil','Dibatalkan'];
    public const ITEMS=['TV','Speaker Aktif Bluetooth','Amplifier','Mixer Audio','Kipas Angin','Laptop','STB','Home Theatre'];
    public const BRANDS=['SONY','LG','Samsung','Sharp','Polytron','Coocaa','Aqua','Toshiba','Panasonic','Philips'];
    public const ACCESSORIES=['Kabel Power','Remote','Adaptor / Charger','Battery','Bracket'];
    public static function customer(array $actor): ?array { return Database::one('SELECT c.*,u.phone FROM customers c JOIN users u ON u.id=c.user_id WHERE c.user_id=? AND c.deleted_at IS NULL',[$actor['id']]); }
    public static function isComplete(array $order): bool { return in_array($order['status']??'',self::COMPLETED_STATUSES,true); }
    public static function validStatusFilter(string $status): bool { return $status==='' || isset(self::STATUS_GROUPS[$status]) || in_array($status,self::STATUSES,true); }
    public static function get(int $id,array $actor): ?array {
        $sql='SELECT s.*,c.user_id FROM service_orders s JOIN customers c ON c.id=s.customer_id WHERE s.id=?'; $p=[$id];
        if ($actor['role']!=='admin') { $sql.=' AND c.user_id=? AND c.deleted_at IS NULL'; $p[]=$actor['id']; }
        return Database::one($sql,$p);
    }
    private static function fields(array $d): array {
        $fields=[]; $fields['received_date']=Validate::date($d['received_date']??'',true);
        foreach (['customer_name'=>150,'address'=>4000,'model'=>120,'complaint'=>4000] as $key=>$max) $fields[$key]=Validate::text($d,$key,$max);
        $fields['contact_phone']=Validate::phone($d['contact_phone']??'');
        foreach (['item_name'=>self::ITEMS,'brand'=>self::BRANDS] as $key=>$options) {
            $v=Validate::text($d,$key,120);
            if ($v==='Lainnya') $v=Validate::text($d,$key.'_custom',120);
            elseif (!in_array($v,$options,true)) throw new ValidationException('Pilihan '.$key.' tidak valid. Gunakan Lainnya untuk pilihan khusus.');
            $fields[$key]=$v;
        }
        $fields['serial_number']=Validate::text($d,'serial_number',150,false);
        $fields['notes']=Validate::text($d,'notes',4000,false);
        $fields['location']=Validate::text($d,'location',30);
        if (!in_array($fields['location'],['Bengkel','Home Service'],true)) throw new ValidationException('Lokasi pengerjaan tidak valid.');
        $access=$d['accessories']??[];
        if (!is_array($access) || count($access)>6) throw new ValidationException('Kelengkapan tidak valid.');
        foreach ($access as $v) if (!is_string($v) || !in_array($v,array_merge(self::ACCESSORIES,['Lainnya']),true)) throw new ValidationException('Kelengkapan tidak valid.');
        if (in_array('Lainnya',$access,true)) { $access=array_values(array_diff($access,['Lainnya'])); $access[]=Validate::text($d,'accessories_custom',200); }
        $fields['accessories']=json_encode(array_values(array_unique($access)),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        return $fields;
    }
    private static function completionFields(array $d,string $receivedDate): array {
        $status=Validate::text($d,'status',40);
        if (!in_array($status,self::STATUSES,true)) throw new ValidationException('Status tidak valid.');
        $complete=self::isComplete(['status'=>$status]);
        $done=$complete?Validate::date($d['completed_date']??null):null; $warranty=$complete?Validate::date($d['warranty_date']??null):null;
        if ($complete && !$done) throw new ValidationException('Tanggal selesai wajib untuk servis selesai/diambil.');
        if ($done && $done<$receivedDate) throw new ValidationException('Tanggal selesai tidak boleh sebelum tanggal masuk.');
        if ($warranty && (!$done || $warranty<$done)) throw new ValidationException('Garansi harus setelah atau sama dengan tanggal selesai.');
        return ['status'=>$status,'completed_date'=>$done,'warranty_date'=>$warranty];
    }
    private static function adminFields(array $d,array $fields): array {
        $values=self::completionFields($d,$fields['received_date']);
        $values+=['total_cost'=>Validate::decimal($d['total_cost']??null),'technician_notes'=>Validate::text($d,'technician_notes',4000,false)];
        if (array_key_exists('show_workshop_signature',$d)) {
            $visible=Validate::integer($d['show_workshop_signature'],0,1);
            if (self::isComplete($values)) $values['show_workshop_signature']=$visible;
        }
        return $values;
    }
    public static function signatureVisible(array $order): bool {
        return self::isComplete($order) && (int)($order['show_workshop_signature']??1)===1;
    }
    public static function create(array $d,array $actor): int {
        if ($actor['role']!=='admin') foreach(['total_cost','status','completed_date','warranty_date','technician_notes','invoice_number','role','customer_id','show_workshop_signature'] as $key) if (array_key_exists($key,$d)) throw new ValidationException('Kolom khusus admin tidak boleh dikirim konsumen.');
        $key=Validate::key($d['submission_key']??'');
        if ($actor['role']==='admin') {
            $customer=Database::one('SELECT c.id,c.name,c.address,u.phone FROM customers c JOIN users u ON u.id=c.user_id WHERE c.id=? AND c.deleted_at IS NULL',[Validate::integer($d['customer_id']??'',1)]);
            if (!$customer) throw new ValidationException('Konsumen tidak ditemukan.');
            foreach(['customer_name'=>'name','contact_phone'=>'phone','address'=>'address'] as $field=>$source) {
                if (!array_key_exists($field,$d) || (is_string($d[$field]) && trim($d[$field])==='')) $d[$field]=$customer[$source];
            }
        }
        $fields=self::fields($d);
        if ($actor['role']==='admin') {
            $fields+=self::adminFields($d,$fields);
        } else { $customer=self::customer($actor); $fields+=['status'=>'Menunggu Konfirmasi','completed_date'=>null,'warranty_date'=>null,'total_cost'=>null,'technician_notes'=>'']; }
        if (!$customer) throw new ValidationException('Akun konsumen tidak ditemukan.');
        return Database::tx(function() use($customer,$key,$fields) {
            // Lock pemilik juga menserialisasi retry token pengiriman untuk konsumen yang sama.
            $owner=Database::one('SELECT id,deleted_at FROM customers WHERE id=? FOR UPDATE',[$customer['id']]);
            if (!$owner || $owner['deleted_at']!==null) throw new ValidationException('Konsumen tidak aktif.');
            $existing=Database::one('SELECT id FROM service_orders WHERE customer_id=? AND submission_key=?',[$customer['id'],$key]);
            if ($existing) return (int)$existing['id'];
            $date=Clock::today();
            Database::query('INSERT IGNORE INTO invoice_sequences(business_date,last_number) VALUES (?,0)',[$date]);
            $seq=Database::one('SELECT last_number FROM invoice_sequences WHERE business_date=? FOR UPDATE',[$date]);
            $number=(int)$seq['last_number']+1;
            Database::query('UPDATE invoice_sequences SET last_number=? WHERE business_date=?',[$number,$date]);
            $invoice='INV-'.str_replace('-','',$date).'-'.str_pad((string)$number,3,'0',STR_PAD_LEFT);
            $values=['customer_id'=>$customer['id'],'invoice_number'=>$invoice,'issued_on'=>$date,'submission_key'=>$key]+$fields;
            Database::query('INSERT INTO service_orders('.implode(',',array_keys($values)).') VALUES ('.implode(',',array_fill(0,count($values),'?')).')',array_values($values));
            return Database::id();
        });
    }
    public static function update(int $id,array $d,array $actor): void {
        Auth::admin($actor); $fields=self::fields($d); $fields+=self::adminFields($d,$fields);
        $revision=Validate::integer($d['revision']??'',1);
        Database::tx(function() use($id,$fields,$revision,$actor) {
            $order=Database::one('SELECT * FROM service_orders WHERE id=? FOR UPDATE',[$id]);
            if (!$order) throw new ValidationException('Servis tidak ditemukan.');
            if ((int)$order['revision']!==$revision) throw new ValidationException('Data servis telah berubah. Muat ulang halaman sebelum menyimpan.');
            self::returnCancelledParts($order,$fields['status'],$actor);
            $sets=implode(',',array_map(fn($k)=>$k.'=?',array_keys($fields)));
            Database::query('UPDATE service_orders SET '.$sets.',revision=revision+1 WHERE id=?',[...array_values($fields),$id]);
        });
    }
    private static function returnCancelledParts(array $order,string $status,array $actor): void {
        if ($status!=='Dibatalkan' || $order['status']==='Dibatalkan') return;
        $parts=Database::all('SELECT * FROM service_order_parts WHERE service_order_id=? AND quantity>0 ORDER BY spare_part_id',[$order['id']]);
        foreach ($parts as $part) Inventory::allocateLocked((int)$order['id'],(int)$part['spare_part_id'],0,$actor,'Pengembalian: servis dibatalkan');
    }
    public static function changeStatus(int $id,array $d,array $actor): void {
        Auth::admin($actor);
        if (array_diff(array_keys($d),['csrf','revision','new_status','completed_date','warranty_date','confirm_cancel','return_to'])) throw new ValidationException('Permintaan ubah status memuat kolom yang tidak diizinkan.');
        $revision=Validate::integer($d['revision']??'',1);
        Database::tx(function() use($id,$d,$actor,$revision) {
            $order=Database::one('SELECT * FROM service_orders WHERE id=? FOR UPDATE',[$id]);
            if (!$order) throw new ValidationException('Servis tidak ditemukan.');
            if ((int)$order['revision']!==$revision) throw new ValidationException('Data servis telah berubah. Muat ulang sebelum mengubah status.');
            $fields=self::completionFields(['status'=>$d['new_status']??'','completed_date'=>$d['completed_date']??null,'warranty_date'=>$d['warranty_date']??null],$order['received_date']);
            if ($fields['status']==='Dibatalkan' && $order['status']!=='Dibatalkan' && ($d['confirm_cancel']??'')!=='1') throw new ValidationException('Konfirmasi pembatalan servis dan pengembalian stok diperlukan.');
            self::returnCancelledParts($order,$fields['status'],$actor);
            Database::query('UPDATE service_orders SET status=?,completed_date=?,warranty_date=?,revision=revision+1 WHERE id=?',[...array_values($fields),$id]);
        });
    }
    public static function summary(array $actor): array {
        $where='c.deleted_at IS NULL'; $params=[];
        if ($actor['role']!=='admin') { $where.=' AND c.user_id=?'; $params[]=$actor['id']; }
        return Database::one("SELECT COUNT(*) total,COALESCE(SUM(s.status='Menunggu Konfirmasi'),0) waiting,COALESCE(SUM(s.status IN ('Menunggu Konfirmasi','Diterima','Dalam Pengerjaan','Menunggu Sparepart')),0) working,COALESCE(SUM(s.status IN ('Selesai','Sudah Diambil')),0) done FROM service_orders s JOIN customers c ON c.id=s.customer_id WHERE ".$where,$params);
    }
    public static function listing(array $actor,string $query='',?string $status=null,int $page=1,bool $includeArchived=false): array {
        $where=[]; $params=[];
        if ($actor['role']!=='admin') { $where[]='c.user_id=? AND c.deleted_at IS NULL'; $params[]=$actor['id']; }
        elseif (!$includeArchived) $where[]='c.deleted_at IS NULL';
        if ($query!=='') {
            $search='%'.$query.'%'; $phone=$query;
            try { $phone=Validate::phone($query); } catch(ValidationException) {}
            $where[]='(s.invoice_number LIKE ? OR s.customer_name LIKE ? OR s.contact_phone LIKE ? OR u.phone LIKE ?)';
            array_push($params,$search,$search,'%'.$phone.'%','%'.$phone.'%');
        }
        if ($status) {
            if (!self::validStatusFilter($status)) throw new ValidationException('Filter status tidak valid.');
            $statuses=self::STATUS_GROUPS[$status]??[$status];
            $where[]='s.status IN ('.implode(',',array_fill(0,count($statuses),'?')).')';
            array_push($params,...$statuses);
        }
        return Database::all('SELECT s.* FROM service_orders s JOIN customers c ON c.id=s.customer_id JOIN users u ON u.id=c.user_id'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY s.id DESC LIMIT 20 OFFSET '.(($page-1)*20),$params);
    }
}
final class Customers {
    public static function save(?int $id,array $d,array $actor): int {
        Auth::admin($actor);
        if ($id===null) return Auth::register($d);
        $name=Validate::text($d,'name',150); $phone=Validate::phone($d['phone']??''); $address=Validate::text($d,'address');
        try { Database::tx(function() use($id,$name,$phone,$address) {
            $c=Database::one('SELECT * FROM customers WHERE id=? AND deleted_at IS NULL FOR UPDATE',[$id]);
            if (!$c) throw new ValidationException('Konsumen tidak ditemukan.');
            Database::query('UPDATE customers SET name=?,address=? WHERE id=?',[$name,$address,$id]);
            Database::query('UPDATE users SET phone=? WHERE id=?',[$phone,$c['user_id']]);
        }); } catch(mysqli_sql_exception $e) { if($e->getCode()===1062) throw new ValidationException('Nomor HP sudah digunakan akun lain.'); throw $e; }
        return $id;
    }
}
final class Inventory {
    public static function save(?int $id,array $d,array $actor): int {
        Auth::admin($actor);
        $fields=['sku'=>Validate::text($d,'sku',60),'name'=>Validate::text($d,'name',150),'minimum_stock'=>Validate::integer($d['minimum_stock']??''),'unit'=>Validate::text($d,'unit',30),'purchase_price'=>Validate::decimal($d['purchase_price']??null),'sale_price'=>Validate::decimal($d['sale_price']??null)];
        try {
            if ($id) {
                $rev=Validate::integer($d['revision']??'',1);
                $s=Database::query('UPDATE spare_parts SET sku=?,name=?,minimum_stock=?,unit=?,purchase_price=?,sale_price=?,revision=revision+1 WHERE id=? AND revision=? AND deleted_at IS NULL',[...array_values($fields),$id,$rev]);
                if (!$s->affected_rows) throw new ValidationException('Data sparepart berubah atau tidak ditemukan. Muat ulang halaman.');
                return $id;
            }
            Database::query('INSERT INTO spare_parts(sku,name,minimum_stock,unit,purchase_price,sale_price) VALUES (?,?,?,?,?,?)',array_values($fields));
            return Database::id();
        } catch(mysqli_sql_exception $e) { if ($e->getCode()===1062) throw new ValidationException('SKU sudah digunakan. Gunakan kode unik.'); throw $e; }
    }
    public static function receive(int $partId,int $quantity,string $reason,string $key,array $actor): void {
        Auth::admin($actor); Validate::integer($quantity,1); Validate::key($key); $reason=Validate::text(['reason'=>$reason],'reason',180);
        Database::tx(function() use($partId,$quantity,$reason,$key,$actor) {
            $part=Database::one('SELECT * FROM spare_parts WHERE id=? FOR UPDATE',[$partId]);
            if (!$part) throw new ValidationException('Sparepart tidak ditemukan.');
            if ($part['deleted_at']!==null) throw new ValidationException('Pulihkan sparepart sebelum menambah stok.');
            if (Database::one('SELECT id FROM stock_movements WHERE operation_key=?',[$key])) return;
            $balance=(int)$part['stock']+$quantity;
            if ($balance>1000000000) throw new ValidationException('Stok melampaui batas.');
            Database::query('UPDATE spare_parts SET stock=?,revision=revision+1 WHERE id=?',[$balance,$partId]);
            Database::query('INSERT INTO stock_movements(spare_part_id,actor_id,delta,balance_after,reason,operation_key) VALUES (?,?,?,?,?,?)',[$partId,$actor['id'],$quantity,$balance,'Stok masuk: '.$reason,$key]);
        });
    }
    public static function adjust(int $partId,int $quantity,int $revision,string $reason,string $key,array $actor): void {
        Auth::admin($actor); Validate::integer($quantity); Validate::integer($revision,1); Validate::key($key);
        $reason=Validate::text(['reason'=>$reason],'reason',180);
        try { Database::tx(function() use($partId,$quantity,$revision,$reason,$key,$actor) {
            $part=Database::one('SELECT * FROM spare_parts WHERE id=? FOR UPDATE',[$partId]);
            if (!$part) throw new ValidationException('Sparepart tidak ditemukan.');
            if ($part['deleted_at']!==null) throw new ValidationException('Pulihkan sparepart sebelum menyesuaikan stok.');
            $previous=Database::one('SELECT * FROM stock_movements WHERE operation_key=?',[$key]);
            $description='Penyesuaian stok: '.$reason;
            if ($previous) {
                $previousReason=$description.' ('.((int)$previous['balance_after']-(int)$previous['delta']).' → '.$quantity.')';
                if ((int)$previous['spare_part_id']!==$partId || (int)$previous['actor_id']!==(int)$actor['id'] || $previous['service_order_id']!==null || (int)$previous['balance_after']!==$quantity || $previous['reason']!==$previousReason) throw new ValidationException('Permintaan stok tidak sesuai. Muat ulang halaman.');
                return;
            }
            if ((int)$part['revision']!==$revision) throw new ValidationException('Stok atau data sparepart sudah berubah. Muat ulang halaman sebelum menyesuaikan stok.');
            $old=(int)$part['stock']; $delta=$quantity-$old;
            if (!$delta) throw new ValidationException('Stok baru masih sama dengan stok tersedia.');
            Database::query('UPDATE spare_parts SET stock=?,revision=revision+1 WHERE id=?',[$quantity,$partId]);
            Database::query('INSERT INTO stock_movements(spare_part_id,actor_id,delta,balance_after,reason,operation_key) VALUES (?,?,?,?,?,?)',[$partId,$actor['id'],$delta,$quantity,$description.' ('.$old.' → '.$quantity.')',$key]);
        }); } catch(mysqli_sql_exception $e) { if ($e->getCode()===1062) throw new ValidationException('Permintaan stok sudah digunakan. Muat ulang halaman.'); throw $e; }
    }
    public static function allocate(int $orderId,int $partId,int $quantity,int $expected,array $actor): void {
        Auth::admin($actor); Validate::integer($quantity); Validate::integer($expected);
        Database::tx(function() use($orderId,$partId,$quantity,$expected,$actor) {
            $order=Database::one('SELECT * FROM service_orders WHERE id=? FOR UPDATE',[$orderId]);
            if (!$order) throw new ValidationException('Servis tidak ditemukan.');
            if ($order['status']==='Dibatalkan') throw new ValidationException('Servis dibatalkan. Buka kembali status servis sebelum mengalokasikan sparepart.');
            $allocation=Database::one('SELECT quantity FROM service_order_parts WHERE service_order_id=? AND spare_part_id=?',[$orderId,$partId]);
            $old=(int)($allocation['quantity']??0);
            // Retry pengiriman kuantitas sama bersifat idempotent; perubahan stale ditolak.
            if ($old===$quantity) return;
            if ($old!==$expected) throw new ValidationException('Alokasi telah berubah. Muat ulang halaman.');
            self::allocateLocked($orderId,$partId,$quantity,$actor,'Penyesuaian penggunaan servis');
        });
    }
    public static function allocateLocked(int $orderId,int $partId,int $quantity,array $actor,string $reason): void {
        // Dipanggil hanya di transaksi setelah row service_orders dikunci.
        $part=Database::one('SELECT * FROM spare_parts WHERE id=? FOR UPDATE',[$partId]);
        if (!$part) throw new ValidationException('Sparepart tidak ditemukan.');
        $usage=Database::one('SELECT * FROM service_order_parts WHERE service_order_id=? AND spare_part_id=? FOR UPDATE',[$orderId,$partId]);
        $old=(int)($usage['quantity']??0); $delta=$old-$quantity; $balance=(int)$part['stock']+$delta;
        if ($part['deleted_at']!==null && $delta<0) throw new ValidationException('Sparepart sudah dihapus. Pulihkan sebelum menambah penggunaan.');
        if (!$delta) return;
        if ($balance<0) throw new ValidationException('Stok tidak cukup. Tersedia '.$part['stock'].' '.$part['unit'].'.');
        if ($balance>1000000000) throw new ValidationException('Stok melampaui batas.');
        Database::query('INSERT INTO service_order_parts(service_order_id,spare_part_id,quantity) VALUES (?,?,?) ON DUPLICATE KEY UPDATE quantity=?',[$orderId,$partId,$quantity,$quantity]);
        Database::query('UPDATE spare_parts SET stock=?,revision=revision+1 WHERE id=?',[$balance,$partId]);
        Database::query('INSERT INTO stock_movements(spare_part_id,service_order_id,actor_id,delta,balance_after,reason,operation_key) VALUES (?,?,?,?,?,?,?)',[$partId,$orderId,$actor['id'],$delta,$balance,$reason.' ('.$old.' → '.$quantity.')',token()]);
    }
}
