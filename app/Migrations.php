<?php
declare(strict_types=1);
final class Migrations {
    public static function apply(): void {
        if (PHP_SAPI!=='cli') throw new RuntimeException('Migrasi hanya melalui CLI.');
        $db=Database::one('SELECT DATABASE() name')['name'];
        $lock='faeyza:migrate:'.substr(hash('sha256',(string)$db),0,40);
        if ((int)Database::one('SELECT GET_LOCK(?,30) acquired',[$lock])['acquired']!==1) throw new RuntimeException('Migrasi sedang berjalan.');
        try {
            $column=Database::one('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?',['service_orders','show_workshop_signature']);
            if (!$column) Database::connection()->query(file_get_contents(ROOT.'/database/migrations/002_workshop_signature.sql'));
            Database::query('INSERT IGNORE INTO schema_migrations(version) VALUES (?)',[2]);
        } finally { Database::query('SELECT RELEASE_LOCK(?)',[$lock]); }
    }
}
