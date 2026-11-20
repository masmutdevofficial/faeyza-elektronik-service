<?php
declare(strict_types=1);
final class RecordDeletion {
    public static function purgeInvoice(int $id,int $revision,array $actor): void {
        Auth::admin($actor);
        self::deleteTransaction(function() use($id,$revision,$actor) {
            $order=Database::one('SELECT * FROM service_orders WHERE id=? FOR UPDATE',[$id]);
            if (!$order) return;
            if ((int)$order['revision']!==$revision) throw new ValidationException('Servis telah berubah. Buka ulang konfirmasi penghapusan.');
            self::eraseOrderLocked($order,$actor);
        });
    }
    public static function purgeCustomer(int $id,array $actor): void {
        Auth::admin($actor);
        self::deleteTransaction(function() use($id,$actor) {
            // The customer lock also blocks new submissions until deletion commits.
            $customer=Database::one('SELECT * FROM customers WHERE id=? FOR UPDATE',[$id]);
            if (!$customer) return;
            $owner=Database::one('SELECT * FROM users WHERE id=? FOR UPDATE',[$customer['user_id']]);
            if (!$owner || $owner['role']!=='customer') throw new ValidationException('Akun admin tidak dapat dihapus di sini.');
            $orders=Database::all('SELECT * FROM service_orders WHERE customer_id=? ORDER BY id FOR UPDATE',[$id]);
            foreach($orders as $order) self::eraseOrderLocked($order,$actor);
            Database::query('DELETE FROM record_events WHERE customer_id=?',[$id]);
            Database::query('DELETE FROM customers WHERE id=?',[$id]);
            Database::query('DELETE FROM users WHERE id=?',[$owner['id']]);
            Database::query('DELETE FROM login_limits WHERE bucket=?',[hash('sha256','phone:'.$owner['phone'])]);
        });
    }
    private static function eraseOrderLocked(array $order,array $actor): void {
        // Completed repairs consumed their parts. Unfinished allocations are returned once.
        if (!Orders::isComplete($order)) {
            $parts=Database::all('SELECT spare_part_id FROM service_order_parts WHERE service_order_id=? AND quantity>0 ORDER BY spare_part_id',[$order['id']]);
            foreach($parts as $part) Inventory::allocateLocked((int)$order['id'],(int)$part['spare_part_id'],0,$actor,'Pengembalian: servis dihapus');
        }
        // Preserve the stock ledger without an invoice/customer reference or personal snapshot.
        Database::query('UPDATE stock_movements SET service_order_id=NULL WHERE service_order_id=?',[$order['id']]);
        Database::query('DELETE FROM service_order_parts WHERE service_order_id=?',[$order['id']]);
        Database::query('DELETE FROM service_orders WHERE id=?',[$order['id']]);
        // invoice_sequences is deliberately retained: issued numbers must never be reused.
    }
    private static function deleteTransaction(callable $callback): void {
        try { Database::tx($callback); }
        catch(mysqli_sql_exception $e) {
            if(in_array($e->getCode(),[1142,1143],true)) throw new ValidationException('Izin hapus database belum disiapkan. Hubungi pengelola untuk memperbarui izin aplikasi.');
            if($e->getCode()===1451) throw new ValidationException('Data masih dirujuk oleh catatan lain. Tidak ada data yang dihapus; hubungi pengelola.');
            throw $e;
        }
    }
    // Legacy archives remain readable/restorable; new customer deletions use purgeCustomer.
    public static function change(string $kind,int $id,bool $deleted,array $actor): void {
        Auth::admin($actor);
        $table=match($kind){'customer'=>'customers','part'=>'spare_parts',default=>throw new ValidationException('Jenis data tidak valid.')};
        Database::tx(function() use($kind,$table,$id,$deleted,$actor) {
            $record=Database::one('SELECT * FROM '.$table.' WHERE id=? FOR UPDATE',[$id]);
            if (!$record) throw new ValidationException('Data tidak ditemukan.');
            if ($kind==='customer') {
                $owner=Database::one('SELECT role FROM users WHERE id=? FOR UPDATE',[$record['user_id']]);
                if (!$owner || $owner['role']!=='customer') throw new ValidationException('Akun admin tidak dapat dihapus di sini.');
            }
            if (($record['deleted_at']!==null)===$deleted) return;
            Database::query('UPDATE '.$table.' SET deleted_at='.($deleted?'NOW()':'NULL').',deleted_by=?'.($kind==='part'?',revision=revision+1':'').' WHERE id=?',[$deleted?$actor['id']:null,$id]);
            if ($kind==='customer') Database::query('UPDATE users SET session_version=session_version+1 WHERE id=?',[$record['user_id']]);
            $foreign=$kind==='customer'?'customer_id':'spare_part_id';
            Database::query('INSERT INTO record_events('.$foreign.',actor_id,action) VALUES (?,?,?)',[$id,$actor['id'],$deleted?'deleted':'restored']);
        });
    }
}
