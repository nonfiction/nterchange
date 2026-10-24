<?php
// Render actual CMS head; a changed site editor config needs a new request URL.
require getenv('NTERCHANGE_TEST_AUTOLOAD') ?: dirname(__DIR__) . '/vendor/autoload.php';
$work = sys_get_temp_dir() . '/nterchange-editor-config-' . bin2hex(random_bytes(6));
mkdir($work, 0700);
foreach (['templates_c', 'smarty_cache', 'public_html', 'public_html/javascripts', 'public_html/stylesheets'] as $directory) mkdir($work . '/' . $directory);
define('ROOT_DIR', $work);
define('BASE_DIR', NTERCHANGE_DIR);
define('APP_DIR', 'nterchange');
define('CURRENT_SITE', 'public');
define('CACHE_DIR', $work);
define('DOCUMENT_ROOT', $work . '/public_html');
define('DEBUG_LEVEL', 0);
define('IN_NTERCHANGE', false);
$_SERVER['SERVER_NAME'] = 'package.invalid';
$_SERVER['PHP_SELF'] = '/nterchange/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$checks = 0;
function same($expected, $actual) {
    global $checks;
    if ($expected !== $actual) throw new RuntimeException(var_export($expected, true) . ' != ' . var_export($actual, true));
    $checks++;
}
try {
    file_put_contents(DOCUMENT_ROOT . '/stylesheets/nterchange_custom.css', '');
    $controller = new class extends NObject {
        public $name = 'fixture', $action = 'index', $flash = null, $page_title = '', $_view_assigns = [];
        public $base_view_dir = ROOT_DIR;
        function debug($message, $type = null, $level = null) {}
    };
    $view = new NView($controller);
    foreach (['_TITLE_' => 'Editor config fixture', 'login_name' => '', 'HEADER' => '', 'navigation' => [], 'layout' => 'default'] as $key => $value) $view->assign($key, $value);
    $render = fn() => $view->fetch(NTERCHANGE_DIR . '/app/views/layouts/head.html');
    same(false, str_contains($render(), 'CKEDITOR.config.customConfig ='));
    $file = DOCUMENT_ROOT . '/javascripts/ckeditor_config.js';
    file_put_contents($file, "CKEDITOR.editorConfig = function(c) { c.contentsCss = '/stylesheets/editor'; };");
    touch($file, 1700000000);
    clearstatcache();
    $first = $render();
    same(1, substr_count($first, 'CKEDITOR.config.customConfig ='));
    same(true, str_contains($first, 'ckeditor_config.js?1700000000'));
    same(true, strpos($first, 'ckeditor/ckeditor.js?') < strpos($first, 'CKEDITOR.config.customConfig ='));
    file_put_contents($file, "CKEDITOR.editorConfig = function(c) { c.contentsCss = '/stylesheets/editor.css'; };");
    touch($file, 1700000010);
    clearstatcache();
    $second = $render();
    same(true, str_contains($second, 'ckeditor_config.js?1700000010'));
    same(false, str_contains($second, 'ckeditor_config.js?1700000000'));
    $view->assign('layout', 'plain');
    same(false, str_contains($render(), 'CKEDITOR.config.customConfig ='));
    $view->assign('layout', 'simple');
    same(false, str_contains($render(), 'CKEDITOR.config.customConfig ='));
    same(PHP_SESSION_NONE, session_status());
    echo "PASS: $checks rendered editor config URL/version checks\n";
} finally {
    foreach (['templates_c', 'smarty_cache', 'public_html/javascripts', 'public_html/stylesheets', 'public_html'] as $directory) {
        foreach (glob($work . '/' . $directory . '/*') as $file) unlink($file);
        rmdir($work . '/' . $directory);
    }
    rmdir($work);
}
