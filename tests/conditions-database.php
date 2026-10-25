<?php
// Real schema-driven find predicates. Use a caller-created disposable schema only.
$database = getenv('DB_DATABASE');
if (!is_string($database) || !str_starts_with($database, 'nterchange_v5_test_')) {
    throw new RuntimeException('DB_DATABASE must name a disposable nterchange_v5_test_* schema');
}
require getenv('NTERCHANGE_TEST_AUTOLOAD') ?: dirname(__DIR__) . '/vendor/autoload.php';
define('ENVIRONMENT', 'development');
define('DEBUG_LEVEL', 0);
define('SITE_TIME_ZONE', 'MST7MDT');
$checks = $failures = 0;
function check($ok, $label) {
    global $checks, $failures;
    $checks++;
    if (!$ok) {
        $failures++;
        echo "FAIL $label\n";
    }
}
function result($value) {
    if (PEAR::isError($value)) throw new RuntimeException($value->getMessage() . ' ' . $value->getUserInfo());
    return $value;
}
class ConditionsFixture extends NModel {
    var $__table = 'v5_conditions_fixture';
}
$db = result(NDB::connect());
result($db->exec('CREATE TABLE v5_conditions_fixture (id INT PRIMARY KEY, number INT, amount DECIMAL(8,2), label VARCHAR(255), day DATE NULL, instant DATETIME NOT NULL, clock TIME NULL, stamp TIMESTAMP NULL, cms_deleted TINYINT NOT NULL DEFAULT 0)'));
try {
    result($db->exec("INSERT INTO v5_conditions_fixture VALUES
        (1,20261025,12.50,'café','2026-10-25','2026-10-25 18:34:56','18:34:56','2026-10-25 18:34:56',0),
        (2,23,0.25,'neighbor','2026-10-26','2026-10-26 18:34:56','-25:01:02','2026-10-26 18:34:56',0),
        (3,42,1.00,'nullable',NULL,'2026-10-27 00:00:00',NULL,NULL,0),
        (4,23,0.25,'deleted','2026-10-25','2026-10-25 18:34:56','18:34:56','2026-10-25 18:34:56',1)"));
    $model = new ConditionsFixture();
    $defs = $model->table();
    check(($defs['day'] & N_DAO_DATE) !== 0, 'real DATE schema flag');
    check(($defs['instant'] & (N_DAO_DATE | N_DAO_TIME)) === (N_DAO_DATE | N_DAO_TIME), 'real DATETIME schema flags');
    check(($defs['clock'] & N_DAO_TIME) !== 0, 'real TIME schema flag');
    check(($defs['stamp'] & (N_DAO_DATE | N_DAO_TIME)) === (N_DAO_DATE | N_DAO_TIME), 'real TIMESTAMP schema flags');
    $cases = [
        ['day', '2026-10-25', [1]],
        ['instant', '2026-10-25 18:34:56', [1]],
        ['clock', '18:34:56', [1]],
        ['stamp', '2026-10-25 18:34:56', [1]],
        ['clock', '-25:01:02', [2]],
        ['day', '20261025', [1]],
        ['number', '20261025', [1]],
        ['amount', '12.50', [1]],
        ['label', 'café', [1]],
        ['day', 'null', [3]],
        ['clock', 'null', [3]],
        ['stamp', 'null', [3]],
        ['day', null, [1, 2, 3]],
    ];
    foreach ($cases as [$field, $value, $expected]) {
        $model = new ConditionsFixture();
        $model->$field = $value;
        $model->find(['order_by' => 'id']);
        $ids = array_map('intval', array_column($model->fetchAll(true), 'id'));
        check($ids === $expected, "$field=" . var_export($value, true) . ' returned ' . json_encode($ids));
    }
    $model = new ConditionsFixture();
    $model->day = '2026-10-25';
    $model->instant = '2026-10-25 18:34:56';
    $model->find();
    check(array_map('intval', array_column($model->fetchAll(true), 'id')) === [1], 'multiple temporal predicates');
    $query = $model->_buildConditions(['conditions' => 'id=1'], $defs, ['day']);
    check($query['conditions'] === "id=1 AND v5_conditions_fixture.day='2026-10-25'", 'filter and existing condition');
    $model->number = '2026-10-25 18:34:56';
    $query = $model->_buildConditions(['conditions' => ''], $defs, ['number']);
    check($query['conditions'] === ' v5_conditions_fixture.number=2026', 'integer coercion unchanged, no value date guessing');
    $model->day = "2026-10-25' OR 1=1 -- ";
    $query = $model->_buildConditions(['conditions' => ''], $defs, ['day']);
    check($query['conditions'] === ' v5_conditions_fixture.day=' . $db->quote($model->day), 'temporal value uses real driver escaping');
    $model->find();
    check(count($model->fetchAll(true)) === 0, 'malformed date cannot widen matches');
    check((int)result($db->queryOne('SELECT COUNT(*) FROM v5_conditions_fixture')) === 4, 'reads preserve all fixture rows');
} finally {
    result($db->exec('DROP TABLE v5_conditions_fixture'));
    $db->disconnect();
}
echo ($failures ? 'FAIL' : 'PASS') . " $checks conditions checks, $failures failures, PHP " . PHP_VERSION . ' charset=' . getenv('DB_CHARSET') . "\n";
exit($failures ? 1 : 0);
