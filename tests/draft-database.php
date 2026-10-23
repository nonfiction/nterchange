<?php
// Actual form/draft/model writes in an owned schema; no HTTP, login or sessions.
$database = getenv('DB_DATABASE');
if (!is_string($database) || !str_starts_with($database, 'nterchange_v5_test_')) {
    throw new RuntimeException('DB_DATABASE must name a disposable nterchange_v5_test_* schema');
}
require getenv('NTERCHANGE_TEST_AUTOLOAD') ?: dirname(__DIR__) . '/vendor/autoload.php';
define('ENVIRONMENT', 'development');
define('DEBUG_LEVEL', 0);
define('ROOT_DIR', NTERCHANGE_DIR);
define('BASE_DIR', NTERCHANGE_DIR);
define('SITE_TIME_ZONE', 'MST7MDT');
define('SITE_DRAFTS', true);
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
class V5DraftFixture extends NModel {
    var $__table = 'v5_draft_fixture';
}
class V5DraftFixtureController extends AppController {
    // Bypass only route/auth setup; inherited form hooks and saveDraft are real.
    function __construct($model) {
        $this->name = 'v5_draft_fixture';
        $this->models = ['v5_draft_fixture' => $model];
    }
}
$db = result(NDB::connect());
result($db->query('SELECT 1'));
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';
check($db->queryOne('SELECT @@character_set_connection') === $charset, 'Connection charset');
check((bool)($db->getOption('portability') & MDB2_PORTABILITY_RTRIM), 'Existing RTRIM policy remains enabled');
$tables = [
    'v5_draft_fixture' => 'id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, cms_headline VARCHAR(255), content LONGTEXT, summary TEXT, scheduled DATETIME, cms_draft TINYINT DEFAULT 0, cms_deleted TINYINT DEFAULT 0',
    'cms_drafts' => 'id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, asset VARCHAR(64), asset_id INT, version_id INT, draft LONGTEXT, cms_created DATETIME, cms_modified DATETIME, cms_modified_by_user INT, cms_deleted TINYINT DEFAULT 0',
    'cms_nterchange_versions' => 'id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, asset_id INT, cms_created DATETIME, cms_deleted TINYINT DEFAULT 0',
];
$created = [];
try {
    foreach ($tables as $table => $columns) {
        result($db->exec("CREATE TABLE $table ($columns)"));
        $created[] = $table;
    }
    $text = $charset === 'utf8mb4' ? 'Published café 中文' : "Published caf\xe9";
    foreach ([$text . "\r\n", $text . " \t  ", $text . "\0", 'null', ''] as $case => $published) {
        result($db->exec('INSERT INTO v5_draft_fixture (cms_headline, content, summary, scheduled) VALUES ('
            . $db->quote('Original headline') . ', ' . $db->quote($published) . ', '
            . $db->quote("Published summary\r\n") . ", '2026-10-23 15:17:00')"));
        $id = result($db->queryOne('SELECT MAX(id) FROM v5_draft_fixture'));
        $select = 'SELECT HEX(content) AS content, HEX(summary) AS summary, scheduled, cms_draft, cms_deleted FROM v5_draft_fixture WHERE id=' . $id;
        $before = result($db->queryRow($select));
        $model = new V5DraftFixture();
        check((bool)$model->get($id), "Case $case model get");
        check($model->content === rtrim($published), "Case $case real MDB2 fetched bytes");
        $controller = new V5DraftFixtureController($model);
        $form = new ControllerForm($controller, $model);
        // First draft inserts; second draft updates the existing serialized row.
        foreach ([1, 2] as $save) {
            $draft = "Draft $case/$save\r\n";
            $headline = "Draft O'Reilly \\ $case/$save  ";
            $summary = "Draft summary $save\r\n";
            check((bool)$form->processForm(['id' => $id, 'content' => $draft, 'summary' => $summary,
                'cms_headline' => $headline, '__submit_draft__' => 'Save as Draft']), "Case $case save $save returns success");
            $after = result($db->queryRow($select));
            check($after['content'] === $before['content'],
                "Case $case save $save published content HEX changed: {$before['content']} -> {$after['content']}");
            check($after === $before, "Case $case save $save all non-headline published fields unchanged");
            check($db->queryOne('SELECT HEX(cms_headline) FROM v5_draft_fixture WHERE id=' . $id) === strtoupper(bin2hex($headline)), "Case $case save $save exact headline");
            $where = " FROM cms_drafts WHERE asset='v5_draft_fixture' AND asset_id=" . $id;
            check((int)$db->queryOne('SELECT COUNT(*)' . $where) === 1, "Case $case save $save single draft row");
            $stored = unserialize(result($db->queryOne('SELECT draft' . $where)), ['allowed_classes' => false]);
            check($stored['content'] === $draft && $stored['summary'] === $summary, "Case $case save $save exact serialized draft fields");
        }
    }
    check(session_status() === PHP_SESSION_NONE, 'No session started');
} finally {
    foreach (array_reverse($created) as $table) result($db->exec("DROP TABLE $table"));
    $db->disconnect();
}
echo "PASS $checks actual form/draft/MySQL byte-preservation checks for $charset on PHP " . PHP_VERSION . "\n";
