<?php
// Exercise actual helper resolution and cache keys without a site or session.
require getenv('NTERCHANGE_TEST_AUTOLOAD') ?: dirname(__DIR__) . '/vendor/autoload.php';
$work = sys_get_temp_dir() . '/nterchange-js-' . bin2hex(random_bytes(6));
mkdir($work, 0700);
foreach (['includes', 'javascripts'] as $directory) mkdir($work . '/' . $directory);
define('DOCUMENT_ROOT', $work);
define('BASE_DIR', NTERCHANGE_DIR);
define('ENVIRONMENT', 'development');
$checks = 0;
function same($expected, $actual) {
    global $checks;
    if ($expected !== $actual) throw new RuntimeException(var_export($expected, true) . ' != ' . var_export($actual, true));
    $checks++;
}
$helper = new AssetTagHelper();
$view = null;
try {
    $file = NTERCHANGE_DIR . '/nterchange/javascripts/ckeditor/ckeditor.js';
    $tag = $helper->javascriptIncludeTagFunc(['src' => 'ckeditor/ckeditor'], $view);
    same('<script src="/nterchange/javascripts/ckeditor/ckeditor.js?' . filemtime($file) . '" type="text/javascript"></script>', $tag);
    same($tag, $helper->javascriptIncludeTagFunc(['src' => 'ckeditor/ckeditor.js'], $view));
    file_put_contents($work . '/javascripts/fixture.js', '// first bytes');
    touch($work . '/javascripts/fixture.js', 1700000000);
    clearstatcache();
    same('<script src="/javascripts/fixture.js?1700000000" type="text/javascript" language="javascript" charset="utf-8"></script>', $helper->javascriptIncludeTagFunc(['src' => 'fixture', 'language' => 'javascript', 'charset' => 'utf-8'], $view));
    file_put_contents($work . '/javascripts/fixture.js', '// replacement package bytes');
    touch($work . '/javascripts/fixture.js', 1700000010);
    clearstatcache();
    same('<script src="/javascripts/fixture.js?1700000010" type="text/javascript"></script>', $helper->javascriptIncludeTagFunc(['src' => 'fixture'], $view));
    file_put_contents($work . '/includes/fixture.js', '// includes override');
    touch($work . '/includes/fixture.js', 1700000020);
    clearstatcache();
    same('<script src="/includes/fixture.js?1700000020" type="text/javascript"></script>', $helper->javascriptIncludeTagFunc(['src' => 'fixture'], $view));
    same(null, $helper->javascriptIncludeTagFunc(['src' => 'nonexistent-fixture'], $view));
    same(null, $helper->javascriptIncludeTagFunc([], $view));
    same('<script src="/javascripts/one.js" type="text/javascript"></script>' . "\n" . '<script src="/javascripts/two.js" type="text/javascript"></script>' . "\n", $helper->javascriptIncludeTagFunc(['src' => 'one,two'], $view));
    same(PHP_SESSION_NONE, session_status());
    echo "PASS: $checks JavaScript resource resolution/cache-key checks\n";
} finally {
    foreach (['includes', 'javascripts'] as $directory) {
        foreach (glob($work . '/' . $directory . '/*') as $file) unlink($file);
        rmdir($work . '/' . $directory);
    }
    rmdir($work);
}
