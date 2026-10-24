<?php
// Render shared layouts without site data, authentication or session startup.
require getenv('NTERCHANGE_TEST_AUTOLOAD') ?: dirname(__DIR__) . '/vendor/autoload.php';
$work = sys_get_temp_dir() . '/nterchange-branding-' . bin2hex(random_bytes(6));
mkdir($work, 0700);
foreach (['templates_c', 'smarty_cache', 'public_html'] as $directory) mkdir($work . '/' . $directory);
define('ROOT_DIR', $work);
define('BASE_DIR', NTERCHANGE_DIR);
define('APP_DIR', 'nterchange');
define('CURRENT_SITE', 'public');
define('CACHE_DIR', $work);
define('DOCUMENT_ROOT', $work . '/public_html');
define('DEBUG_LEVEL', 0);
define('IN_NTERCHANGE', false);
$_SERVER += ['SERVER_NAME' => 'package.invalid', 'PHP_SELF' => '/nterchange/site_admin', 'QUERY_STRING' => '', 'REQUEST_METHOD' => 'GET', 'HTTP_USER_AGENT' => 'layout-fixture', 'REMOTE_ADDR' => '127.0.0.1'];
$checks = 0;
function same($expected, $actual) {
    global $checks;
    if ($expected !== $actual) throw new RuntimeException(var_export($expected, true) . ' != ' . var_export($actual, true));
    $checks++;
}
function branding_unused_row_check() {
    throw new RuntimeException('Empty row fixtures must not invoke model/template checks');
}
function branding_url_for($params) {
    return '/nterchange/' . ($params['controller'] ?? 'site_admin') . '/' . ($params['action'] ?? 'index') . (isset($params['id']) ? '/' . $params['id'] : '');
}
try {
    $controller = new class extends NObject {
        public $name = 'fixture', $action = 'index', $flash = null, $page_title = '', $_view_assigns = [];
        public $base_view_dir = ROOT_DIR;
        function debug($message, $type = null, $level = null) {}
    };
    $view = new NView($controller);
    $view->register_function('url_for', 'branding_url_for');
    foreach (['check_template', 'check_asset_model', 'check_asset_controller', 'check_asset_table'] as $helper) {
        $view->register_function($helper, 'branding_unused_row_check');
    }
    $view->assign(['_TITLE_' => 'Fixture', '_FLASH_' => [], 'HEADER' => '', 'SIDEBAR_CONTENT' => '', 'MAIN_CONTENT' => '<form name="login_form"><input name="username"><input name="password"><button>Sign In</button></form>', 'forgot' => false, 'subnav' => false, 'nterchange_version' => 'fixture']);
    foreach (['login', 'default', 'plain', 'simple'] as $layout) {
        $authenticated = $layout !== 'login';
        $view->assign('login_name', $authenticated ? 'Owned fixture' : '');
        $view->assign('navigation', $authenticated ? [['controller' => 'site_admin', 'action' => 'index', 'id' => '', 'title' => 'Site Admin', 'class' => 'current', 'target' => '']] : []);
        $html = $view->fetch(NTERCHANGE_DIR . '/app/views/layouts/' . $layout . '.html');
        same(1, substr_count($html, 'id="header"'));
        same(1, substr_count($html, 'id="logo"'));
        same($authenticated ? 1 : 0, substr_count($html, 'id="logout"'));
        same($authenticated ? 1 : 0, substr_count($html, 'id="navigation"'));
        same(true, str_contains($html, 'src="/nterchange/images/nterchange.gif" alt="nterchange" width="149" height="29"'));
        if ($layout !== 'simple') same(true, str_contains($html, 'src="/nterchange/images/created_by.gif" alt="created by nonfiction" width="111" height="13"'));
        same(true, str_contains($html, '<form name="login_form">'));
        if ($authenticated) same(true, strpos($html, 'id="navigation"') < strpos($html, 'id="container"'));
    }
    foreach ([[3, true, ['clear_all_caches', 'surf_to_edit', 'new_page']], [1, true, ['surf_to_edit', 'new_page']], [3, false, ['clear_all_caches']], [1, false, []]] as [$level, $edit, $expected]) {
        $view->assign(['user_level' => $level, 'page_edit' => $edit, 'content' => '', 'sitemap_list' => '<ul id="fixture-tree"></ul>']);
        $html = $view->fetch(NTERCHANGE_DIR . '/app/views/site_admin/site_admin.html');
        preg_match_all('~<p><a[^>]+href="([^"]+)"[^>]*><img src="/nterchange/images/([a-z_]+)\.gif"~', $html, $matches);
        same($expected, $matches[2]);
        $urls = ['clear_all_caches' => '/nterchange/site_admin/clear_all_cache', 'surf_to_edit' => '/nterchange/page/surftoedit/1', 'new_page' => '/nterchange/page/create'];
        same(array_map(fn($name) => $urls[$name], $expected), $matches[1]);
        same(true, str_contains($html, '<ul id="fixture-tree">'));
    }
    foreach (['users' => 'Users', 'page_template' => 'Templates', 'cms_asset_info' => 'Assets'] as $asset => $name) {
        $view->assign(['asset' => $asset, 'asset_name' => $name, 'rows' => []]);
        $content = $view->fetch(NTERCHANGE_DIR . '/app/views/' . $asset . '/viewlist.html');
        same(true, str_contains($content, '<div class="control">'));
        same(true, str_contains($content, 'class="control">New ' . $name . '</a>'));
        same(true, str_contains($content, 'href="/nterchange/site_admin/create"'));
        $view->assign(['MAIN_CONTENT' => $content, 'subnav' => [['controller' => $asset, 'action' => 'viewlist', 'id' => '', 'title' => $name, 'class' => 'current', 'target' => '']]]);
        $html = $view->fetch(NTERCHANGE_DIR . '/app/views/layouts/default.html');
        same(true, str_contains($html, '<div class="pane">'));
        same(true, strpos($html, 'id="subnav"') < strpos($html, 'class="pane"'));
        same(true, strpos($html, 'class="pane"') < strpos($html, 'class="control"'));
    }
    $screen = file_get_contents(NTERCHANGE_DIR . '/nterchange/stylesheets/screen.css');
    same(true, str_contains($screen, 'layout.css?v=5-layout-1'));
    same(true, str_contains($screen, 'lists.css?v=5-layout-1'));
    foreach (['nterchange' => [149, 29], 'created_by' => [111, 13], 'clear_all_caches' => [110, 15], 'surf_to_edit' => [81, 15], 'new_page' => [62, 15], 'bg_header' => [96, 96], 'bg_footer' => [38, 38], 'tab_off' => [70, 25], 'tab_on' => [72, 27], 'tab_over' => [70, 25]] as $name => $size) {
        $path = NTERCHANGE_DIR . '/nterchange/images/' . $name . '.gif';
        same(true, is_file($path));
        same($size, array_slice(getimagesize($path), 0, 2));
    }
    same(PHP_SESSION_NONE, session_status());
    echo "PASS: $checks rendered branding/layout/button checks\n";
} finally {
    foreach (['templates_c', 'smarty_cache', 'public_html'] as $directory) {
        foreach (glob($work . '/' . $directory . '/*') as $file) unlink($file);
        rmdir($work . '/' . $directory);
    }
    rmdir($work);
}
