<?php
declare(strict_types=1);
$page=$_GET['page']??'dashboard';
if (!is_string($page)) abort_page(404);
$routes=['login','admin-login','register','logout','account','customer-password','customer-delete','customer-restore','part-delete','part-restore','dashboard','orders','invoices','service-new','service-edit','service-status','order','invoice','success','customers','customer','customer-new','customer-edit','parts','part','part-new','part-edit'];
if (!in_array($page,$routes,true)) abort_page(404);
$method=$_SERVER['REQUEST_METHOD'];
if (!in_array($method,['GET','POST'],true)) { header('Allow: GET, POST'); abort_page(405); }
$adminRoutes=['customers','customer','customer-new','customer-edit','customer-password','customer-delete','customer-restore','part-delete','part-restore','parts','part','part-new','part-edit','service-edit','service-status'];
$user=current_user();
if (!in_array($page,['login','admin-login','register'],true)) $user=require_login(in_array($page,$adminRoutes,true)?'admin-login':'login');
if (in_array($page,$adminRoutes,true)) $user=require_admin();
if ($method==='POST' && (!isset($_POST['csrf']) || !is_string($_POST['csrf']) || !hash_equals($_SESSION['csrf'],$_POST['csrf']))) abort_page(419);
$id=isset($_GET['id']) && is_string($_GET['id']) && ctype_digit($_GET['id']) ? (int)$_GET['id'] : 0;
$error=''; $data=$method==='POST'?$_POST:[];
if (in_array($page,['login','admin-login','register'],true)) {
    if ($user) redirect('dashboard');
    if ($method==='POST') {
        try {
            if ($page==='register') {
                if (Validate::password($_POST,'password',Auth::PASSWORD_MIN)!==Validate::password($_POST,'password_confirm',Auth::PASSWORD_MIN)) throw new ValidationException('Konfirmasi password tidak sama.');
                $uid=Auth::register($_POST);
                $auth=Database::one('SELECT * FROM users WHERE id=?',[$uid]);
            } else {
                $auth=Auth::login(Validate::text($_POST,'phone',40),Validate::password($_POST,'password',1),$_SERVER['REMOTE_ADDR']??'unknown');
                if ($auth['role']!==($page==='admin-login'?'admin':'customer')) throw new ValidationException('Nomor HP atau password salah.');
                $uid=(int)$auth['id'];
            }
            sign_in($auth); redirect('dashboard');
        } catch(ValidationException $e) { $error=$e->getMessage(); http_response_code(422); }
    }
    render('auth',compact('page','data','error')); return;
}
if ($page==='logout') {
    if ($method!=='POST') { header('Allow: POST'); abort_page(405); }
    $loginPage=$user['role']==='admin'?'admin-login':'login';
    $_SESSION=[];
    $cookie=session_get_cookie_params();
    setcookie(session_name(),'', ['expires'=>time()-42000,'path'=>$cookie['path'],'secure'=>$cookie['secure'],'httponly'=>true,'samesite'=>'Lax']);
    session_destroy(); redirect($loginPage);
}
if ($page==='account') {
    if ($method==='POST') {
        try {
            $action=Validate::text($_POST,'action',30);
            if ($action==='phone') { if ($user['role']!=='admin') abort_page(403); $updated=Accounts::changePhone($_POST,$user); }
            elseif ($action==='password') $updated=Accounts::changePassword($_POST,$user);
            else throw new ValidationException('Permintaan tidak valid.');
            sign_in($updated);
            flash($action==='phone'?'Nomor HP berhasil diperbarui.':'Password berhasil diperbarui.'); redirect('account');
        } catch(ValidationException $e) { $error=$e->getMessage(); http_response_code(422); }
    }
    render('account',compact('data','error')); return;
}
if ($page==='customer-password') {
    $customer=Database::one("SELECT c.id,c.name,u.phone FROM customers c JOIN users u ON u.id=c.user_id WHERE c.id=? AND u.role='customer' AND c.deleted_at IS NULL",[$id]);
    if (!$customer) abort_page(404);
    if ($method==='POST') {
        try { Accounts::resetCustomerPassword($id,$_POST,$user); flash('Password pelanggan berhasil diperbarui.'); redirect('customer',['id'=>$id]); }
        catch(ValidationException $e) { $error=$e->getMessage(); http_response_code(422); }
    }
    render('customer-password',compact('customer','error')); return;
}
if (in_array($page,['customer-delete','customer-restore','part-delete','part-restore'],true)) {
    $kind=str_starts_with($page,'customer-')?'customer':'part'; $label=$kind==='customer'?'konsumen':'sparepart';
    $table=$kind==='customer'?'customers':'spare_parts';
    $record=Database::one('SELECT * FROM '.$table.' WHERE id=?',[$id]); if (!$record) abort_page(404);
    $restore=str_ends_with($page,'-restore');
    if ($restore && $method!=='POST') abort_page(405);
    if ($method==='POST') {
        try {
            if (!$restore && ($_POST['confirm']??'')!=='1') throw new ValidationException('Centang konfirmasi penghapusan.');
            RecordDeletion::change($kind,$id,!$restore,$user);
            flash(ucfirst($label).($restore?' berhasil dipulihkan.':' dihapus dari daftar aktif.'));
            if ($restore) redirect($kind,['id'=>$id]);
            redirect($kind==='customer'?'customers':'parts',['deleted'=>'1']);
        } catch(ValidationException $e) { $error=$e->getMessage(); http_response_code(422); }
    }
    if ($record['deleted_at']!==null) redirect($kind,['id'=>$id]);
    render('record-delete',compact('record','kind','label','error')); return;
}
if ($page==='dashboard') {
    if ($method!=='GET') abort_page(405);
    $stats=Orders::summary($user);
    $customers= $user['role']==='admin' ? Database::one('SELECT COUNT(*) n FROM customers WHERE deleted_at IS NULL')['n'] : null;
    $low= $user['role']==='admin' ? Database::one('SELECT COUNT(*) n FROM spare_parts WHERE stock<=minimum_stock AND deleted_at IS NULL')['n'] : null;
    $recent=array_slice(Orders::listing($user),0,6);
    render('dashboard',compact('stats','customers','low','recent')); return;
}
if (in_array($page,['orders','invoices'],true)) {
    if ($method!=='GET') abort_page(405);
    try { $q=Validate::text($_GET,'q',150,false); $status=Validate::text($_GET,'status',40,false); } catch(ValidationException) { abort_page(400); }
    if (!Orders::validStatusFilter($status)) abort_page(400);
    $p=max(1,min(100000,(int)(is_scalar($_GET['p']??1)?($_GET['p']??1):1)));
    $orders=Orders::listing($user,$q,$status?:null,$p,$page==='invoices');
    render('orders',compact('orders','page','q','status','p')); return;
}
if (in_array($page,['order','invoice','success'],true)) {
    if ($method!=='GET') abort_page(405);
    $order=Orders::get($id,$user); if (!$order) abort_page(404);
    if ($page==='invoice') { render('invoice',compact('order')); return; }
    $parts=[]; $available=[];
    if ($user['role']==='admin') {
        $parts=Database::all('SELECT sop.*,p.name,p.sku,p.unit,p.stock,p.deleted_at FROM service_order_parts sop JOIN spare_parts p ON p.id=sop.spare_part_id WHERE sop.service_order_id=? ORDER BY p.name',[$id]);
        $available=Database::all('SELECT p.*,COALESCE(sop.quantity,0) allocated FROM spare_parts p LEFT JOIN service_order_parts sop ON sop.spare_part_id=p.id AND sop.service_order_id=? WHERE p.deleted_at IS NULL ORDER BY p.name',[$id]);
    }
    render('order',compact('order','page','parts','available','error')); return;
}
if ($page==='service-status') {
    $statusOrder=Orders::get($id,$user); if (!$statusOrder) abort_page(404);
    $returnTo=($method==='POST'?($_POST['return_to']??'order'):($_GET['return_to']??'order'));
    if (!is_string($returnTo) || !in_array($returnTo,['order','orders'],true)) abort_page(400);
    $statusBackParams=['id'=>$id]; $statusRouteParams=['id'=>$id,'return_to'=>$returnTo];
    if ($returnTo==='orders') {
        try {
            $returnQuery=Validate::text($_GET,'q',150,false); $returnStatus=Validate::text($_GET,'filter_status',40,false);
            $returnPage=Validate::integer($_GET['p']??1,1,100000);
            if (!Orders::validStatusFilter($returnStatus)) throw new ValidationException('Filter tidak valid.');
        } catch(ValidationException) { abort_page(400); }
        $statusBackParams=['q'=>$returnQuery,'status'=>$returnStatus,'p'=>$returnPage];
        $statusRouteParams+=['q'=>$returnQuery,'filter_status'=>$returnStatus,'p'=>$returnPage];
    }
    if ($method==='POST') {
        try {
            Orders::changeStatus($id,$_POST,$user);
            flash('Status servis berhasil diperbarui.');
            redirect($returnTo,$statusBackParams);
        } catch(ValidationException $e) { $error=$e->getMessage(); http_response_code(422); }
    } else {
        $data=['new_status'=>$statusOrder['status'],'revision'=>$statusOrder['revision'],'completed_date'=>Orders::isComplete($statusOrder)?$statusOrder['completed_date']:null,'warranty_date'=>Orders::isComplete($statusOrder)?$statusOrder['warranty_date']:null];
    }
    render('service-status',compact('statusOrder','data','returnTo','error','statusBackParams','statusRouteParams')); return;
}
// Penggunaan sparepart dikirim ke service-edit, selalu berotorisasi admin.
if ($page==='service-new' || $page==='service-edit') {
    $editing=$page==='service-edit';
    $order=$editing?Orders::get($id,$user):null;
    if ($editing && !$order) abort_page(404);
    $lockedCustomer=null;
    if ($user['role']==='admin' && !$editing && array_key_exists('customer_id',$_GET)) {
        try { $selectedCustomerId=Validate::integer($_GET['customer_id'],1); }
        catch(ValidationException) { abort_page(400); }
        $lockedCustomer=Database::one('SELECT c.id,c.name,c.address,u.phone FROM customers c JOIN users u ON u.id=c.user_id WHERE c.id=? AND c.deleted_at IS NULL',[$selectedCustomerId]);
        if (!$lockedCustomer) abort_page(404);
    }
    if ($method==='POST') {
        try {
            $action=Validate::text($_POST,'action',30,false);
            if ($editing && $action==='allocate') {
                Inventory::allocate($id,Validate::integer($_POST['part_id']??'',1),Validate::integer($_POST['quantity']??''),Validate::integer($_POST['expected']??''),$user);
                flash('Penggunaan sparepart disimpan. Stok dan histori telah diperbarui.'); redirect('order',['id'=>$id]);
            }
            if ($editing) { Orders::update($id,$_POST,$user); flash('Perubahan servis berhasil disimpan.'); redirect('order',['id'=>$id]); }
            $submission=$_POST;
            if ($lockedCustomer) {
                if (array_key_exists('customer_id',$submission) && Validate::integer($submission['customer_id'],1)!==(int)$lockedCustomer['id']) throw new ValidationException('Konsumen pesanan tidak sesuai dengan halaman yang dibuka.');
                $submission['customer_id']=$lockedCustomer['id'];
            }
            $newId=Orders::create($submission,$user); redirect('success',['id'=>$newId]);
        } catch(ValidationException $e) {
            $error=$e->getMessage(); http_response_code(422);
            if ($editing && ($_POST['action']??'')==='allocate') {
                $parts=Database::all('SELECT sop.*,p.name,p.sku,p.unit,p.stock,p.deleted_at FROM service_order_parts sop JOIN spare_parts p ON p.id=sop.spare_part_id WHERE sop.service_order_id=?',[$id]);
                $available=Database::all('SELECT p.*,COALESCE(sop.quantity,0) allocated FROM spare_parts p LEFT JOIN service_order_parts sop ON sop.spare_part_id=p.id AND sop.service_order_id=? WHERE p.deleted_at IS NULL ORDER BY p.name',[$id]);
                render('order',compact('order','page','parts','available','error')); return;
            }
        }
    } else {
        $profile=Orders::customer($user);
        $data=$order??['received_date'=>Clock::today(),'customer_name'=>$profile['name']??'','contact_phone'=>$profile['phone']??'','address'=>$profile['address']??'','location'=>'Bengkel','status'=>'Diterima'];
        $data['submission_key']=token();
        if ($order) $data['accessories']=json_decode($order['accessories'],true);
    }
    $customers=$user['role']==='admin' && !$editing && !$lockedCustomer?Database::all('SELECT c.id,c.name,c.address,u.phone FROM customers c JOIN users u ON u.id=c.user_id WHERE c.deleted_at IS NULL ORDER BY c.name'):[];
    if ($lockedCustomer) $data['customer_id']=$lockedCustomer['id'];
    if ($user['role']==='admin' && !$editing) {
        foreach($lockedCustomer?[$lockedCustomer]:$customers as $customer) if(($data['customer_id']??'')==$customer['id']) {
            foreach(['customer_name'=>'name','contact_phone'=>'phone','address'=>'address'] as $field=>$source) {
                if (!array_key_exists($field,$data) || (is_string($data[$field]) && trim($data[$field])==='')) $data[$field]=$customer[$source];
            }
            break;
        }
    }
    render('service-form',compact('data','editing','error','id','customers','lockedCustomer')); return;
}
if ($page==='customers') {
    if ($method!=='GET') abort_page(405);
    try { $q=Validate::text($_GET,'q',150,false); } catch(ValidationException) { abort_page(400); }
    $phone=$q; try{$phone=Validate::phone($q);}catch(ValidationException){}
    $p=max(1,min(100000,(int)(is_scalar($_GET['p']??1)?($_GET['p']??1):1)));
    $deleted=($_GET['deleted']??'')==='1';
    $customers=Database::all('SELECT c.*,u.phone,(SELECT COUNT(*) FROM service_orders s WHERE s.customer_id=c.id) total FROM customers c JOIN users u ON u.id=c.user_id WHERE (c.name LIKE ? OR u.phone LIKE ?) AND c.deleted_at IS '.($deleted?'NOT NULL':'NULL').' ORDER BY c.id DESC LIMIT 20 OFFSET '.(($p-1)*20),['%'.$q.'%','%'.$phone.'%']);
    render('customers',compact('customers','q','p','deleted')); return;
}
if (in_array($page,['customer','customer-edit','customer-new'],true)) {
    $editing=$page!=='customer-new';
    $customer=$editing?Database::one('SELECT c.*,u.phone FROM customers c JOIN users u ON u.id=c.user_id WHERE c.id=?',[$id]):null;
    if ($editing && !$customer) abort_page(404);
    if ($editing && $page!=='customer' && $customer['deleted_at']!==null) abort_page(404);
    if ($page==='customer') {
        if ($method!=='GET') abort_page(405);
        $orders=Database::all('SELECT * FROM service_orders WHERE customer_id=? ORDER BY id DESC LIMIT 100',[$id]); render('customer',compact('customer','orders')); return;
    }
    if ($method==='POST') {
        try { $saved=Customers::save($editing?$id:null,$_POST,$user); if (!$editing) $saved=(int)Database::one('SELECT id FROM customers WHERE user_id=?',[$saved])['id']; flash('Informasi konsumen berhasil disimpan.'); redirect('customer',['id'=>$saved]); }
        catch(ValidationException $e) { $error=$e->getMessage(); http_response_code(422); }
    } else $data=$customer??[];
    render('customer-form',compact('data','editing','error','id')); return;
}
if ($page==='parts') {
    if ($method!=='GET') abort_page(405);
    try { $q=Validate::text($_GET,'q',150,false); } catch(ValidationException) { abort_page(400); }
    $p=max(1,min(100000,(int)(is_scalar($_GET['p']??1)?($_GET['p']??1):1)));
    $low=($_GET['low']??'')==='1';
    $deleted=($_GET['deleted']??'')==='1';
    $parts=Database::all('SELECT * FROM spare_parts WHERE (sku LIKE ? OR name LIKE ? OR part_type LIKE ?) AND deleted_at IS '.($deleted?'NOT NULL':'NULL').($low?' AND stock<=minimum_stock':'').' ORDER BY id DESC LIMIT 20 OFFSET '.(($p-1)*20),['%'.$q.'%','%'.$q.'%','%'.$q.'%']);
    render('parts',compact('parts','q','p','low','deleted')); return;
}
if (in_array($page,['part','part-new','part-edit'],true)) {
    $editing=$page!=='part-new';
    $part=$editing?Database::one('SELECT * FROM spare_parts WHERE id=?',[$id]):null;
    if ($editing && !$part) abort_page(404);
    if ($editing && $page!=='part' && $part['deleted_at']!==null) abort_page(404);
    if ($method==='POST') {
        try {
            if ($page==='part') {
                $stockAction=Validate::text($_POST,'action',20,false)?:'receive';
                if ($stockAction==='adjust') {
                    Inventory::adjust($id,Validate::integer($_POST['stock_quantity']??''),Validate::integer($_POST['stock_revision']??'',1),Validate::text($_POST,'stock_reason',180),Validate::key($_POST['stock_key']??''),$user);
                    flash('Penyesuaian stok berhasil dicatat.');
                } elseif ($stockAction==='receive') {
                    // Older open forms may still send reason; never convert old supplier notes into a part type.
                    $partType=array_key_exists('part_type',$_POST)?Validate::text($_POST,'part_type',180):null;
                    $receiptReason=$partType??Validate::text($_POST,'reason',180);
                    Inventory::receive($id,Validate::integer($_POST['quantity']??'',1),$receiptReason,Validate::key($_POST['operation_key']??''),$user,$partType);
                    flash('Stok masuk berhasil dicatat.');
                } else throw new ValidationException('Tindakan stok tidak valid.');
                redirect('part',['id'=>$id]);
            }
            $saved=Inventory::save($editing?$id:null,$_POST,$user); flash('Data sparepart berhasil disimpan.'); redirect('part',['id'=>$saved]);
        } catch(ValidationException $e) { $error=$e->getMessage(); http_response_code(422); }
    } else $data=$part??['minimum_stock'=>3,'unit'=>'pcs'];
    if ($page==='part') {
        $p=max(1,min(100000,(int)(is_scalar($_GET['p']??1)?($_GET['p']??1):1)));
        $movements=Database::all('SELECT m.*,s.invoice_number,u.phone actor_phone FROM stock_movements m LEFT JOIN service_orders s ON s.id=m.service_order_id JOIN users u ON u.id=m.actor_id WHERE m.spare_part_id=? ORDER BY m.id DESC LIMIT 20 OFFSET '.(($p-1)*20),[$id]);
        $data['operation_key']??=token(); $data['stock_key']??=token();
        $data['part_type']??=$part['part_type'];
        $data['stock_revision']??=$part['revision']; $data['stock_quantity']??=$part['stock'];
        render('part',compact('part','movements','data','error','p')); return;
    }
    render('part-form',compact('data','editing','error','id','part')); return;
}
abort_page(404);
