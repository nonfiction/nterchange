<?php
// Real filesystem helpers, image bytes and NView/Smarty. No site bootstrap or IO outside this fixture.
ini_set('display_errors', 'stderr'); // Keep legacy notices visible without mixing them into rendered HTML.
require getenv('NTERCHANGE_TEST_AUTOLOAD') ?: dirname(__DIR__) . '/vendor/autoload.php';
$work = sys_get_temp_dir() . '/nterchange-downloads-' . bin2hex(random_bytes(6));
mkdir($work, 0700);
define('ROOT_DIR', $work);
define('BASE_DIR', NTERCHANGE_DIR);
define('APP_DIR', 'nterchange');
define('CURRENT_SITE', 'public');
define('CACHE_DIR', $work);
define('DOCUMENT_ROOT', $work . '/public_html');
define('DEBUG_LEVEL', 0);
define('IN_NTERCHANGE', false);
$_SERVER['DOCUMENT_ROOT'] = DOCUMENT_ROOT;
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
try {
    foreach (['templates_c', 'smarty_cache', 'public_html', 'public_html/images', 'public_html/images/icons', 'public_html/uploads'] as $directory) mkdir($work . '/' . $directory);
    foreach (['pdf' => [2, 3], 'default' => [4, 5]] as $name => [$width, $height]) {
        $image = imagecreatetruecolor($width, $height);
        imagepng($image, DOCUMENT_ROOT . '/images/icons/' . $name . '.png');
    }
    file_put_contents(DOCUMENT_ROOT . '/uploads/report.pdf', str_repeat('x', 4096));
    $filesystem = new FilesystemHelper();
    $tags = new AssetTagHelper();
    $known = '<img src="/images/icons/pdf.png" alt="Download" width="2" height="3" style="border:0;" />';
    $fallback = '<img src="/images/icons/default.png" alt="Download" width="4" height="5" style="border:0;" />';
    same('4 KB', $filesystem->filesizeFormat('/uploads/report.pdf'));
    same('4 KB', $filesystem->filesizeFormat(DOCUMENT_ROOT . '/uploads/report.pdf'));
    same(1.0, $filesystem->downloadTime('/uploads/report.pdf'));
    same(false, $filesystem->filesizeFormat('/uploads/missing.pdf'));
    same(false, $filesystem->downloadTime('/uploads/missing.pdf'));
    same($known, $filesystem->downloadIcon(['file' => '/uploads/report.pdf']));
    same($known, NFilesystem::download_icon(['file' => '/uploads/report.pdf']));
    same($fallback, $filesystem->downloadIcon(['file' => '/uploads/report.xyz']));
    same($known, $tags->imageTag('/images/icons/pdf.png', 'Download'));
    same($known, AssetTagHelper::imageTag('/images/icons/pdf.png', 'Download'));
    $controller = new class extends NObject {
        public $name = 'fixture', $action = 'index', $flash = null, $page_title = '', $_view_assigns = [];
        public $base_view_dir = ROOT_DIR;
        function debug($message, $type = null, $level = null) {}
    };
    $view = new NView($controller);
    file_put_contents($work . '/downloads.html', 'START|{download_icon file=$file}|{$file|filesize_format}|{$file|download_time}|END');
    $view->assign('file', '/uploads/report.pdf');
    same('START|' . $known . '|4 KB|1|END', $view->fetch($work . '/downloads.html'));
    same('START|' . $known . '|4 KB|1|END', $view->fetch($work . '/downloads.html'));
    $view->assign('file', '/uploads/report.xyz');
    same('START|' . $fallback . '|||END', $view->fetch($work . '/downloads.html'));
    same(PHP_SESSION_NONE, session_status());
    same(false, in_array(ROOT_DIR . '/conf/conf.php', get_included_files(), true));
    echo "PASS: $checks filesystem/download rendering checks\n";
} finally {
    removeFixture($work);
}
