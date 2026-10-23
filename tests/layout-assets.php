<?php
// Render the real package head through NView/Smarty. No site config or HTTP.
require getenv('NTERCHANGE_TEST_AUTOLOAD') ?: dirname(__DIR__) . '/vendor/autoload.php';
$work = sys_get_temp_dir() . '/nterchange-layout-' . bin2hex(random_bytes(6));
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
$_SERVER['SERVER_NAME'] = 'package.invalid';
$_SERVER['PHP_SELF'] = '/nterchange/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$checks = 0;
function same($expected, $actual) {
    global $checks;
    if ($expected !== $actual) throw new RuntimeException(var_export($expected, true) . ' != ' . var_export($actual, true));
    $checks++;
}
$assets = [
    'prototype/1.7.3/prototype.js' => '46bc7c7b853bf69ab0b165153453f7c1e84bf6982fe8adb6245088a5f3de8360',
    'scriptaculous/1.9.0/scriptaculous.min.js' => 'c8b3a3ca35f0953f722e994ec5e531fe875957c2026be64fe4715c76cfcadc43',
    'scriptaculous/1.9.0/builder.min.js' => '87ef2672ff15efd88088a57a60b0f7b2a07460d98c40aebc0dd14d7cf8be67e7',
    'scriptaculous/1.9.0/effects.min.js' => '00e4b1e87461d5c4149ccb265d7bfa413121dbcf94d71fff025b2830c1203099',
    'scriptaculous/1.9.0/dragdrop.min.js' => '7f39d08b09f1b29b9d24a4ed836ff88bd04191b7f2ea220162bed73fe1407d41',
    'scriptaculous/1.9.0/slider.min.js' => 'eab709c2f1ca793fb71c1592a670e6fd47efa0e5f5f1db2497dff88c9c673ee1',
    'scriptaculous/1.9.0/controls.min.js' => '1e76abae1fdde589e713b97504e84259195e6f0b77911089e4634e00119ac889',
];
try {
    $controller = new class extends NObject {
        public $name = 'fixture', $action = 'index', $flash = null, $page_title = '', $_view_assigns = [];
        public $base_view_dir = ROOT_DIR;
        function debug($message, $type = null, $level = null) {}
    };
    $view = new NView($controller);
    $view->assign('_TITLE_', 'Package fixture');
    $view->assign('login_name', '');
    $view->assign('HEADER', '');
    $expected = array_map(fn($file) => '/nterchange/javascripts/' . $file, array_keys($assets));
    foreach (['default', 'plain', 'simple'] as $layout) {
        $view->assign('layout', $layout);
        $html = $view->fetch(NTERCHANGE_DIR . '/app/views/layouts/head.html');
        preg_match_all('/<script\b[^>]*\bsrc="([^"]+)"/i', $html, $matches);
        same($expected, array_slice($matches[1], 0, count($expected)));
        foreach ($matches[1] as $src) {
            same(true, str_starts_with($src, '/nterchange/javascripts/'));
            same(true, is_file(NTERCHANGE_DIR . parse_url($src, PHP_URL_PATH)));
        }
    }
    foreach ($assets as $file => $sha256) {
        same($sha256, hash_file('sha256', NTERCHANGE_DIR . '/nterchange/javascripts/' . $file));
    }
    same(PHP_SESSION_NONE, session_status());
    echo "PASS: $checks rendered layout/resource checks\n";
} finally {
    foreach (['templates_c', 'smarty_cache', 'public_html'] as $directory) {
        foreach (glob($work . '/' . $directory . '/*') as $file) unlink($file);
        rmdir($work . '/' . $directory);
    }
    rmdir($work);
}
