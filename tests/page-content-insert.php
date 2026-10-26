<?php
// Real Add New Content controller and QuickForm; persistence/auth are simulated.
namespace NterchangePageContentInsertTest;
require getenv('NTERCHANGE_TEST_AUTOLOAD') ?: dirname(__DIR__) . '/vendor/autoload.php';
define('CURRENT_SITE', 'public');
define('APP_DIR', 'nterchange');
define('SITE_WORKFLOW', false);
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['PHP_SELF'] = '/nterchange/page_content/addnewcontent/42';
class NAuth {}
class NController {
    static $asset;
    static function &singleton($name) { return self::$asset; }
}
#[\AllowDynamicProperties]
class nterchangeController {
    public $params = [];
    public $values = [];
    public $rendered;
    function &loadModel($name) {
        $page = new class {
            public $title = 'Owned page';
            function get($id) {}
        };
        return $page;
    }
    function set($values) { $this->values = $values; }
    function render($options) { $this->rendered = $options; }
}
class ControllerForm {
    public $form;
    static $result;
    function __construct($controller, $model) {
        $this->form = new \NQuickForm('insert_fixture');
        $this->form->addElement('text', 'cms_headline', 'Headline');
        $this->form->addElement('textarea', 'content', 'Content');
        $this->form->addElement('submit', '__submit__', 'Save');
    }
    function &getForm() { return $this->form; }
    function processForm($values) { return self::$result; }
}
$source = file_get_contents(NTERCHANGE_DIR . '/app/controllers/page_content_controller.php');
eval('namespace NterchangePageContentInsertTest; use \\NQuickForm; use \\Inflector;' . substr($source, 5));
class FixtureController extends PageContentController {
    function __construct() { $this->name = 'page_content'; }
}
NController::$asset = new #[\AllowDynamicProperties] class {
    public $name = 'text';
    function &getDefaultModel() {
        $model = new class {
            public $id;
            function table() { return []; }
            function fields() { return []; }
            function primaryKey() { return 'id'; }
        };
        return $model;
    }
};
$checks = 0;
function check($ok, $label) {
    global $checks;
    if (!$ok) throw new \RuntimeException($label);
    $checks++;
}
// A failed save must not exit through the success redirect.
register_shutdown_function(function () {
    if ((error_get_last()['type'] ?? null) === E_ERROR) return;
    if ($GLOBALS['checks'] !== 44) throw new \RuntimeException('Controller exited before rendering rejected form');
});
foreach ([false, 0] as $failure) {
    foreach (['/nterchange/page/content/42', null] as $referer) {
        ControllerForm::$result = $failure;
        $_POST = ['cms_headline' => 'Unsaved <headline>', 'content' => "<p>Unsaved & text</p>\r\n", '__submit__' => 'Save'];
        $_POST['timed_start'] = $_POST['timed_end'] = ['Y'=>'', 'm'=>'', 'd'=>'', 'H'=>'', 'i'=>''];
        $controller = new FixtureController();
        $controller->params = ['asset' => 'text', 'template_container_id' => 3];
        if ($referer !== null) {
            $controller->params['_referer'] = $referer;
            $_POST['_referer'] = urlencode($referer);
        }
        http_response_code(200);
        $controller->addNewContent(42);
        $html = $controller->values['form'] ?? '';
        check(http_response_code() === 503, 'Failed insert must return 503 instead of redirecting');
        check(str_contains($html, 'Content could not be saved.'), 'Failure must be visible');
        check(str_contains($html, 'select any files again'), 'Explain file selection is not retained');
        check(str_contains($html, 'Unsaved &lt;headline&gt;'), 'Retain escaped headline');
        check(str_contains($html, '&lt;p&gt;Unsaved &amp; text&lt;/p&gt;'), 'Retain escaped body');
        check($controller->rendered === ['action' => 'form', 'layout' => 'default'], 'Render editable form');
        foreach (['timed_start', 'timed_end'] as $field) {
            foreach (['H', 'i'] as $part) {
                check((bool) preg_match('/<select name="' . $field . '\\[' . $part . '\\]">\\s*<option value="" selected="selected">/', $html), 'Retain blank schedule ' . $field . ' ' . $part);
            }
        }
        if ($referer !== null) {
            check(str_contains($html, 'value="' . urlencode($referer) . '"'), 'Retain return URL for retry');
        }
    }
}
$form = new \NQuickForm('midnight_fixture');
$date = $form->addElement('date', 'midnight', 'Date', ['format'=>'Y-m-d H:i', 'addEmptyOption'=>true]);
$date->setValue(['Y'=>'2027', 'm'=>'01', 'd'=>'15', 'H'=>'00', 'i'=>'00']);
$html = $date->toHTML();
foreach (['H', 'i'] as $part) {
    check((bool) preg_match('/<select name="midnight\\[' . $part . '\\]">.*?<option value="0" selected="selected">/s', $html), 'Midnight remains zero, not blank');
}
echo "PASS $checks Add New Content failure/controller/widget checks (simulated persistence/auth)\n";
