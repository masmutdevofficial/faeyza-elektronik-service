<?php
declare(strict_types=1);
function reset_test_database(): void {
    if(getenv('APP_ENV')!=='testing' || !str_ends_with((string)getenv('DB_NAME'),'_test')) throw new RuntimeException('Reset hanya diperbolehkan pada database _test dengan APP_ENV=testing.');
    Database::connection()->query('SET FOREIGN_KEY_CHECKS=0');
    foreach(['record_events','stock_movements','service_order_parts','service_orders','invoice_sequences','customers','users','spare_parts','login_limits'] as $table) Database::connection()->query('TRUNCATE TABLE '.$table);
    Database::connection()->query('SET FOREIGN_KEY_CHECKS=1');
}
