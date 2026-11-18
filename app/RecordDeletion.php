<?php
declare(strict_types=1);
final class RecordDeletion {
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
