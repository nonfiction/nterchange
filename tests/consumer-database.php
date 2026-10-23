<?php
// Construct a committed application Text override against a synthetic table.
// Use an isolated consumer copy, never an existing site config or database.
$database = getenv('DB_DATABASE');
$root = getenv('NTERCHANGE_TEST_CONSUMER_ROOT');
$autoload = getenv('NTERCHANGE_TEST_AUTOLOAD');
if (!$root || !$autoload || !is_string($database) || !str_starts_with($database, 'nterchange_v5_test_')) {
    throw new RuntimeException('Explicit disposable consumer root, autoload and nterchange_v5_test_* schema required');
}
define('ROOT_DIR', $root);
define('APP_DIR', 'nterchange');
define('CURRENT_SITE', 'public');
define('ENVIRONMENT', 'development');
define('DEBUG_LEVEL', 0);
define('SITE_TIME_ZONE', 'MST7MDT');
require $autoload;
define('BASE_DIR', NTERCHANGE_DIR);
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
$db = result(NDB::connect());
$charset = getenv('DB_CHARSET');
check($db->queryOne('SELECT @@character_set_connection') === $charset, 'Connection charset');
result($db->exec('CREATE TABLE text (id INT AUTO_INCREMENT PRIMARY KEY, cms_headline VARCHAR(255), content MEDIUMTEXT, cms_deleted TINYINT NOT NULL DEFAULT 0)'));
$uploads = sys_get_temp_dir() . '/nterchange-v5-uploads-' . bin2hex(random_bytes(8));
define('UPLOAD_DIR', $uploads);
define('DOCUMENT_ROOT', $uploads);
try {
    mkdir($uploads, 0700);
    $model = NModel::factory('text');
    check($model instanceof Text && $model instanceof NModel, 'Application model factory');
    check(realpath((new ReflectionClass($model))->getFileName()) === realpath(ROOT_DIR . '/app/models/text.php'), 'Application override wins');
    check(in_array('content', $model->fields(), true), 'Real schema introspection');
    $content = $charset === 'utf8mb4' ? '<p>Fixture café 中文</p>' : '<p>Fixture café</p>';
    $model->cms_headline = 'Disposable package fixture';
    $model->content = $content;
    $model->cms_deleted = 0;
    $id = $model->insert();
    check(is_numeric($id) && $id > 0, 'Inherited insert');
    $read = NModel::factory('text');
    check((bool)$read->get($id), 'Inherited get');
    check($read->content === $content, 'Content byte round trip');
    $read->content = '<p>Updated café</p>';
    check((int)$read->update() === 1, 'Inherited update');
    check($db->queryOne('SELECT content FROM text') === '<p>Updated café</p>', 'Update persistence');
    mkdir($uploads . '/text/' . $id, 0700, true);
    file_put_contents($uploads . '/text/' . $id . '/fixture.txt', 'Owned fixture only');
    check((int)$read->delete() === 1, 'Inherited soft delete');
    check(!is_dir($uploads . '/text/' . $id), 'Inherited per-record upload cleanup');
    check((int)$db->queryOne('SELECT cms_deleted FROM text') === 1, 'Delete persistence');
    check(session_status() === PHP_SESSION_NONE, 'No session started');
} finally {
    result($db->exec('DROP TABLE text'));
    NFilesystem::deletePathRecursive($uploads);
    $db->disconnect();
}
echo "PASS $checks actual application Text factory/constructor/CRUD checks for $charset on PHP " . PHP_VERSION . "\n";
