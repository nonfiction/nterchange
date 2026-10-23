<?php
// Real cache/error paths in owned temporary storage. No DB, HTTP or sessions.
require getenv('NTERCHANGE_TEST_AUTOLOAD') ?: dirname(__DIR__) . '/vendor/autoload.php';
require_once 'Cache/Lite.php';

if (($argv[1] ?? '') === 'debug') {
    $cache = new Cache_Lite();
    $cache->setToDebug();
    $cache->raiseError('cache debug fixture', -3);
    throw new RuntimeException('Debug error mode did not stop execution');
}

$checks = 0;
function same($expected, $actual) {
    global $checks;
    if ($expected !== $actual) throw new RuntimeException(var_export($expected, true) . ' != ' . var_export($actual, true));
    $checks++;
}
function cacheError($error, $code, $message) {
    same(true, $error instanceof PEAR_Error);
    same($code, $error->getCode());
    same($message, $error->getMessage());
    same(PEAR_ERROR_RETURN, $error->mode);
}

$work = sys_get_temp_dir() . '/nterchange-cache-' . bin2hex(random_bytes(6));
mkdir($work, 0700);
mkdir($work . '/ntercache', 0700);
define('CACHE_DIR', $work);
try {
    same(PHP_SESSION_NONE, session_status());
    ob_start();
    $missing = NCache::removeMenu();
    same('', ob_get_clean());
    cacheError($missing, -3, 'Cache_Lite : Unable to remove cache !');
    cacheError(NCache::removeMenu(), -3, 'Cache_Lite : Unable to remove cache !');
    cacheError(NCache::removeTreeAsSelect(), -3, 'Cache_Lite : Unable to remove cache !');

    $content = [7 => ['title' => 'cache fixture', 'parent_id' => null]];
    same(true, NCache::createMenu($content));
    same($content, NCache::getMenu());
    same(true, NCache::removeMenu());
    same(null, NCache::getMenu());
    same(true, NCache::createTreeAsSelect($content));
    same($content, NCache::getTreeAsSelect());
    same(true, NCache::removeTreeAsSelect());
    same(null, NCache::getTreeAsSelect());

    // A regular file used as a parent path fails even when tests run as root.
    file_put_contents($work . '/blocker', 'owned fixture');
    $cache = new Cache_Lite(['cacheDir' => $work . '/blocker/']);
    same(false, $cache->save('value', 'write-failure'));
    $cache->setOption('errorHandlingAPIBreak', true);
    cacheError($cache->save('value', 'write-failure'), -1,
        'Cache_Lite : Unable to write cache file : ' . $cache->_file);

    $cache = new Cache_Lite(['pearErrorMode' => PEAR_ERROR_PRINT]);
    ob_start();
    $printed = $cache->raiseError('cache print fixture', -7);
    same('cache print fixture', ob_get_clean());
    same(true, $printed instanceof PEAR_Error);
    same(-7, $printed->getCode());
    same(PEAR_ERROR_PRINT, $printed->mode);

    $process = proc_open([PHP_BINARY, __FILE__, 'debug'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    same(0, proc_close($process));
    same("cache debug fixture\n", $stdout);
    fwrite(STDERR, $stderr); // Keep existing PEAR deprecations visible.
    same(PHP_SESSION_NONE, session_status());
    echo "PASS: $checks cache checks\n";
} finally {
    foreach (glob($work . '/ntercache/*') as $file) unlink($file);
    if (is_file($work . '/blocker')) unlink($work . '/blocker');
    rmdir($work . '/ntercache');
    rmdir($work);
}
