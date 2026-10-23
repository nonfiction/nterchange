<?php
// Real package classes and filesystem. No site bootstrap, sessions, DB or HTTP.
require getenv('NTERCHANGE_TEST_AUTOLOAD') ?: dirname(__DIR__) . '/vendor/autoload.php';
$work = sys_get_temp_dir() . '/nterchange-v5-' . bin2hex(random_bytes(6));
mkdir($work);
define('ROOT_DIR', $work);
define('BASE_DIR', NTERCHANGE_DIR);
define('APP_DIR', 'nterchange');
define('CURRENT_SITE', 'public');
define('CACHE_DIR', $work);
define('DOCUMENT_ROOT', $work . '/public_html');
define('UPLOAD_DIR', '/uploads');
define('ASSET_DIR', $work . '/app');
define('DEBUG_LEVEL', 0);
define('SITE_TIME_ZONE', 'MST7MDT');
define('IN_NTERCHANGE', false);
$_SERVER['SERVER_NAME'] = 'package.invalid';
$_SERVER['REQUEST_METHOD'] = 'GET';
$checks = 0;
function same($expected, $actual) {
    global $checks;
    if ($expected !== $actual) throw new RuntimeException(var_export($expected, true) . ' != ' . var_export($actual, true));
    $checks++;
}
function removeFixture($path) {
    foreach (scandir($path) as $name) {
        if ($name === '.' || $name === '..') continue;
        $file = $path . '/' . $name;
        is_dir($file) ? removeFixture($file) : unlink($file);
    }
    rmdir($path);
}
register_shutdown_function(function () use ($work) { removeFixture($work); });

same('5.0.0-dev', NTERCHANGE_VERSION);
same(PHP_SESSION_NONE, session_status());
foreach (['NObject', 'NController', 'NModel', 'NDate', 'NQuickForm', 'NView', 'ControllerForm', 'PageController', 'UsersController', 'AuditTrailController'] as $class) {
    same(true, class_exists($class));
}
require_once 'HTML/QuickForm/Rule/Compare.php';
$blank = array_fill_keys(['Y','m','d','H','i'], '');
$start = ['Y'=>'2026','m'=>'10','d'=>'23','H'=>'9','i'=>'17'];
$end = ['Y'=>'2027','m'=>'2','d'=>'13','H'=>'16','i'=>'43'];
foreach ([[$blank,$blank],[$start,$end]] as $dates) {
    $_POST = ['asset_id'=>['7','11'],'timed_start'=>$dates[0],'timed_end'=>$dates[1]];
    $form = new HTML_QuickForm('existing_content','post','/fixture-only');
    $form->addElement('select','asset_id','Asset',['7'=>'First','11'=>'Second'],['multiple'=>'multiple']);
    $options = ['format'=>'Y-m-d H:i','minYear'=>2026,'maxYear'=>2030,'addEmptyOption'=>true];
    $form->addElement('date','timed_start','Start',$options);
    $form->addElement('date','timed_end','End',$options);
    same($_POST, $form->exportValues());
    same($dates[0], $form->exportValue('timed_start'));
    same($dates[1], $form->exportValue('timed_end'));
}
same([7=>'left',11=>'right'], HTML_QuickForm::arrayMerge([7=>'left'], [11=>'right']));
same(['a'=>[7=>'keep','x'=>'replace',11=>'new']], HTML_QuickForm::arrayMerge(['a'=>[7=>'keep','x'=>'old']], ['a'=>[11=>'new','x'=>'replace']]));
same(['a'=>[11=>'new']], HTML_QuickForm::arrayMerge(['a'=>'old'], ['a'=>[11=>'new']]));
same(['a'=>'new'], HTML_QuickForm::arrayMerge(['a'=>[7=>'old']], ['a'=>'new']));
$compare = new HTML_QuickForm_Rule_Compare();
foreach (['eq','==','===',null,'unknown'] as $operator) same(true, $compare->validate([7,'7'], $operator));
same(false, $compare->validate(['07',7], 'eq'));
foreach (['neq','!=','!=='] as $operator) same(true, $compare->validate(['07',7], $operator));
foreach (['gt','>','gte','>='] as $operator) same(true, $compare->validate(['10','2'], $operator));
foreach (['lt','<','lte','<='] as $operator) same(true, $compare->validate(['2','10'], $operator));
same(false, $compare->validate(['2','2'], 'gt'));
same(true, $compare->validate(['2','2.0'], 'gte'));
$form = new HTML_QuickForm();
$button = $form->addElement('button', 'choose', 'Choose', ['onclick'=>'fixtureOnly()']);
same('button', $button->getType());
same('Choose', $button->getValue());
same('fixtureOnly()', $button->getAttribute('onclick'));
$form->addElement('html', '<p>Fixture markup.</p>');
same(true, str_contains($form->toHtml(), '<p>Fixture markup.</p>'));
require_once 'HTML/QuickForm/Rule/Range.php';
$range = new HTML_QuickForm_Rule_Range();
same(true, $range->validate('five', [2,6]));
same(false, $range->validate('too long', [2,6]));
$_POST = [];
$nform = new NQuickForm();
same(true, $nform instanceof HTML_QuickForm);
same('2026-10-23 15:17:00', NDate::convertTimeToUTC('2026-10-23 09:17:00', '%Y-%m-%d %H:%M:%S'));
same('2026-01-23 16:17:00', NDate::convertTimeToUTC('2026-01-23 09:17:00', '%Y-%m-%d %H:%M:%S'));

mkdir(DOCUMENT_ROOT);
mkdir(DOCUMENT_ROOT . '/owned');
file_put_contents(DOCUMENT_ROOT . '/owned/only.txt', 'fixture');
same(true, NFilesystem::deletePathRecursive('/owned'));
same(false, file_exists(DOCUMENT_ROOT . '/owned'));

// Real template compilation, reuse and site override selection in a separate root.
mkdir($work . '/templates_c');
mkdir($work . '/smarty_cache');
mkdir($work . '/app/views/fixture', 0777, true);
file_put_contents($work . '/app/views/fixture/index.html', 'Hello {$name|escape}!');
$controller = new class extends NObject {
    public $name = 'fixture', $action = 'index', $flash = null, $page_title = '', $_view_assigns = [];
    public $base_view_dir = ROOT_DIR, $view_caching = false, $view_cache_lifetime = 0, $view_cache_name = null;
    function debug($message, $type = null, $level = null) {}
};
$view = NView::singleton($controller);
$view->assign('name', '<package>');
same('Hello &lt;package&gt;!', $view->fetch($view->_getViewFileName('index')));
same('Hello &lt;package&gt;!', $view->fetch($view->_getViewFileName('index')));
same(NTERCHANGE_DIR . '/app/views/layouts/default.html', $view->_getLayoutFileName('default'));
mkdir($work . '/app/views/layouts');
file_put_contents($work . '/app/views/layouts/default.html', 'override');
same($work . '/app/views/layouts/default.html', $view->_getLayoutFileName('default'));

// Package controller lookup must not require vendor/nterchange or a symlink.
same(NTERCHANGE_DIR . '/app/controllers/page_controller.php', NController::getIncludePath('page'));
mkdir($work . '/app/controllers');
file_put_contents($work . '/app/controllers/page_controller.php', '<?php // path-only override');
same($work . '/app/controllers/page_controller.php', NController::getIncludePath('page'));

putenv('DB_DATABASE=fixture');
putenv('DB_CHARSET=latin1');
same(true, str_ends_with(NDB::dsn(), '/fixture?charset=latin1'));
putenv('DB_CHARSET=utf8mb4');
same(true, str_ends_with(NDB::dsn(), '/fixture?charset=utf8mb4'));
require_once 'vendor/spyc.php';
same(['version'=>'5.0.0-dev', 'enabled'=>true], Spyc::YAMLLoad("version: \"5.0.0-dev\"\nenabled: true\n"));
same(['assets'=>['one.js','two.js']], Spyc::YAMLLoad("assets:\n  - one.js\n  - two.js\n"));
$directView = new NView($controller);
same($work . '/app/views/fixture/index.html', $directView->_getViewFileName('index'));
same(false, NController::factory('package_fixture_missing_controller'));
same('', $directView->render(['status'=>404, 'nothing'=>true]));
same(404, http_response_code());
http_response_code(200);

// Regression for CVE-2015-10009. Generated PHP must treat payload as data.
class V5CodeCallee extends NObject {
    function output($params) { return $params['q']; }
}
$caller = (new ReflectionClass(CodeCallerController::class))->newInstanceWithoutConstructor();
$callee = new V5CodeCallee();
$payload = "\\');\$GLOBALS['v5InjectionRan']=true;/*";
file_put_contents($work . '/callee.php', '<?php // fixture class already loaded');
$generated = $caller->dynamicPHP($callee, 'output', $work . '/callee.php', ['q'=>$payload]);
ob_start();
eval('?>' . $generated);
$output = ob_get_clean();
same($payload, $output);
same(false, isset($GLOBALS['v5InjectionRan']));

$directory = getcwd();
chdir(NTERCHANGE_DIR . '/nterchange/images/otf');
$_GET['text'] = 'new page';
ob_start();
include 'button.php';
$gif = ob_get_clean();
chdir($directory);
$image = getimagesizefromstring($gif);
same('image/gif', $image['mime']);
same(63, $image[0]);
same(15, $image[1]);
echo "PASS $checks real package loading, form/date, filesystem, template and image checks on PHP " . PHP_VERSION . "\n";
