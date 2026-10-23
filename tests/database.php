<?php
// Real MySQL/MDB2/model/Auth contract against a caller-created disposable schema.
// Refuse ordinary site database names. Never invoke against an existing site.
$database = getenv('DB_DATABASE');
if (!is_string($database) || !str_starts_with($database, 'nterchange_v5_test_')) {
    throw new RuntimeException('DB_DATABASE must name a disposable nterchange_v5_test_* schema');
}
require getenv('NTERCHANGE_TEST_AUTOLOAD') ?: dirname(__DIR__) . '/vendor/autoload.php';
define('ENVIRONMENT', 'development');
define('DEBUG_LEVEL', 0);
define('SITE_TIME_ZONE', 'MST7MDT');
$checks = 0;
function check($ok, $label) {
    global $checks;
    if (!$ok) throw new RuntimeException($label);
    $checks++;
}
function result($value) {
    if (PEAR::isError($value)) throw new RuntimeException($value->getMessage() . ' ' . $value->getUserInfo());
    return $value;
}
$db = NDB::connect();
result($db);
result($db->query('SELECT 1'));
check(str_starts_with(result($db->queryOne('SELECT VERSION()')), '8.4.'), 'MySQL 8.4 required for this test');
$charset = getenv('DB_CHARSET');
check(result($db->queryOne('SELECT @@character_set_connection')) === $charset, 'Connection charset');
result($db->exec('CREATE TABLE v5_fixture (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, scheduled DATETIME NULL, cms_deleted TINYINT NOT NULL DEFAULT 0)'));
try {
    class V5Fixture extends NModel {
        var $__table = 'v5_fixture';
        var $_delete_uploads = false;
    }
    $model = new V5Fixture();
    check(in_array('scheduled', $model->fields(), true), 'Schema introspection');
    $text = $charset === 'utf8mb4' ? 'Package café 中文' : 'Package café';
    $model->title = $text;
    $model->scheduled = 'null';
    $model->cms_deleted = 0;
    $id = $model->insert();
    check(is_numeric($id) && $id > 0, 'Real model insert');
    check((int)$db->queryOne('SELECT COUNT(*) FROM v5_fixture WHERE scheduled IS NULL') === 1, 'SQL NULL insert');
    $read = new V5Fixture();
    check((bool)$read->get($id), 'Model get');
    check($read->title === $text, 'Charset round trip');
    $read->title = 'Updated café';
    $read->scheduled = '2026-10-23 09:17:00';
    check((int)$read->update() === 1, 'Model update');
    check($db->queryOne('SELECT scheduled FROM v5_fixture') === '2026-10-23 15:17:00', 'Model local-to-UTC persistence');
    $read->scheduled = 'null';
    result($read->update());
    check((int)$db->queryOne('SELECT COUNT(*) FROM v5_fixture WHERE scheduled IS NULL') === 1, 'Repeated NULL clear');
    result($read->update());
    check((int)$db->queryOne('SELECT COUNT(*) FROM v5_fixture WHERE scheduled IS NULL') === 1, 'Idempotent NULL clear');
    check((int)$read->delete() === 1, 'Soft delete');
    check((int)$db->queryOne('SELECT cms_deleted FROM v5_fixture') === 1, 'Delete persistence');
    check(PEAR::isError($db->query('SELECT missing_fixture_column FROM v5_fixture')), 'Query failure stays a PEAR error, not a mysqli exception');
} finally {
    result($db->exec('DROP TABLE v5_fixture'));
}
// Auth container checks password matching without starting any session or mail.
result($db->exec('CREATE TABLE v5_auth_fixture (id INT PRIMARY KEY, username VARCHAR(64), password VARCHAR(64), real_name VARCHAR(64))'));
try {
    result($db->exec("INSERT INTO v5_auth_fixture VALUES (1, 'fixture', '" . md5('fixture-only-password') . "', 'Fixture User')"));
    require_once 'Auth.php';
    require_once 'Auth/Container/MDB2.php';
    $auth = new Auth_Container_MDB2(['dsn'=>NDB::dsn(), 'table'=>'v5_auth_fixture', 'usernamecol'=>'username', 'passwordcol'=>'password', 'cryptType'=>'md5']);
    check($auth->fetchData('fixture', 'wrong') === false, 'Auth rejects wrong password');
    check($auth->fetchData('fixture', 'fixture-only-password') === true, 'Auth accepts current legacy password contract');
} finally {
    result($db->exec('DROP TABLE v5_auth_fixture'));
}
$db->disconnect();
echo "PASS $checks real MySQL/MDB2/model/Auth checks for $charset on PHP " . PHP_VERSION . "\n";
