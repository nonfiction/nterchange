<?php
// Real SQL rejection and form flow; call recording does not simulate an upload.
$database = getenv('DB_DATABASE');
if (!is_string($database) || !str_starts_with($database, 'nterchange_v5_test_')) {
    throw new RuntimeException('DB_DATABASE must name a disposable nterchange_v5_test_* schema');
}
require getenv('NTERCHANGE_TEST_AUTOLOAD') ?: dirname(__DIR__) . '/vendor/autoload.php';
define('ENVIRONMENT', 'development');
define('DEBUG_LEVEL', 0);
define('SITE_TIME_ZONE', 'MST7MDT');
define('SITE_DRAFTS', false);
define('SITE_WORKFLOW', false);
define('SITE_AUDIT_TRAIL', false);
$_FILES = [];
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
class V5InsertFixture extends NModel {
    var $__table = 'v5_insert_fixture';
    var $events = [];
    var $return_zero = false;
    function insert($dao = false) {
        $this->events[] = 'insert';
        return $this->return_zero ? 0 : parent::insert($dao);
    }
    function update($dao = false, $convertTimeToUTC = true) {
        $this->events[] = 'update';
        return parent::update($dao, $convertTimeToUTC);
    }
}
class V5InsertFixtureController extends AppController {
    // Only route/auth construction is bypassed.
    function __construct($model) {
        $this->name = 'v5_insert_fixture';
        $this->models = ['v5_insert_fixture' => $model];
    }
    function postProcessForm(&$values) { $this->models[$this->name]->events[] = 'post'; }
}
class V5InsertFixtureForm extends ControllerForm {
    function processFiles(&$values, $files_array) {
        $this->model->events[] = 'files';
        parent::processFiles($values, $files_array);
    }
}
$db = result(NDB::connect());
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';
check(result($db->queryOne('SELECT @@character_set_connection')) === $charset, 'Connection charset');
result($db->exec('CREATE TABLE v5_insert_fixture (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, cms_headline VARCHAR(255), content LONGTEXT, cms_deleted TINYINT DEFAULT 0)'));
try {
    result($db->exec('INSERT INTO v5_insert_fixture (cms_headline, content) VALUES (\'Anchor\', ' . $db->quote("Published\r\n") . ')'));
    $anchor = result($db->queryOne('SELECT id FROM v5_insert_fixture'));
    $select = 'SELECT id, HEX(cms_headline), HEX(content), cms_deleted FROM v5_insert_fixture ORDER BY id';
    $before = result($db->queryAll($select));
    result($db->exec("CREATE TRIGGER v5_insert_reject BEFORE INSERT ON v5_insert_fixture FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Owned fixture rejected insert'"));
    foreach ([false, true] as $zero) {
        foreach ([true, false] as $posted_id) {
            $model = new V5InsertFixture();
            $model->return_zero = $zero;
            $controller = new V5InsertFixtureController($model);
            $form = new V5InsertFixtureForm($controller, $model);
            $values = ['cms_headline' => 'Rejected', 'content' => 'Must not publish'];
            if ($posted_id) $values['id'] = $anchor;
            check($form->processForm($values) === ($zero ? 0 : false), 'Exact failed insert return');
            check(result($db->queryAll($select)) === $before, 'Failed insert changed existing row; calls=' . implode(',', $model->events));
            check($model->events === ['insert'], 'Failed insert reached file processing, update or post hook');
        }
    }
    result($db->exec('DROP TRIGGER v5_insert_reject'));
    foreach ([true, false] as $posted_id) {
        $model = new V5InsertFixture();
        $controller = new V5InsertFixtureController($model);
        $form = new V5InsertFixtureForm($controller, $model);
        $values = ['cms_headline' => 'New', 'content' => "New body\r\n"];
        if ($posted_id) $values['id'] = $anchor;
        $id = $form->processForm($values);
        check(is_numeric($id) && (int)$id > (int)$anchor && (string)$id === (string)$model->id, 'Successful insert returns newly assigned ID');
        check($model->events === ['insert', 'files', 'update', 'post'], 'Successful insert call order unchanged');
        check(result($db->queryRow($select . ' LIMIT 1')) === $before[0], 'Successful insert preserves anchor');
        check(result($db->queryOne('SELECT HEX(content) FROM v5_insert_fixture WHERE id=' . (int)$id)) === strtoupper(bin2hex($values['content'])), 'Successful insert exact content');
    }
    check(session_status() === PHP_SESSION_NONE, 'No session started');
} finally {
    result($db->exec('DROP TABLE v5_insert_fixture'));
    $db->disconnect();
}
echo "PASS $checks form insert/MySQL failure and success checks for $charset on PHP " . PHP_VERSION . "\n";
