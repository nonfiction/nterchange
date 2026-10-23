<?php
// Load committed application overrides in a disposable Composer consumer.
// No application controller/model constructor, site config or request runs.
$root = getenv('NTERCHANGE_TEST_CONSUMER_ROOT');
$autoload = getenv('NTERCHANGE_TEST_AUTOLOAD');
if (!$root || !$autoload) throw new RuntimeException('Explicit disposable consumer root and autoload required');
define('ROOT_DIR', $root);
define('APP_DIR', 'nterchange');
define('CURRENT_SITE', 'public');
define('ENVIRONMENT', 'development');
define('DEBUG_LEVEL', 0);
define('SITE_TIME_ZONE', 'MST7MDT');
require $autoload;
define('BASE_DIR', NTERCHANGE_DIR);
if (!Composer\InstalledVersions::isInstalled('nonfiction/nterchange')) throw new RuntimeException('Composer dependency metadata missing');
$checks = 1;
foreach (['NObject','NController','NModel','NQuickForm','NView','ControllerForm','PageController','UsersController','AuditTrailController'] as $class) {
    if (!class_exists($class)) throw new RuntimeException('Core class missing: ' . $class);
    $file = (new ReflectionClass($class))->getFileName();
    if (!str_starts_with($file, NTERCHANGE_DIR . '/')) throw new RuntimeException('Old vendor loaded: ' . $class);
    $checks++;
}
foreach (['text','html_header','body_image','media_element','ck_upload','news_release','job_posting'] as $name) {
    $file = ROOT_DIR . '/app/controllers/' . $name . '_controller.php';
    if (!is_file($file)) continue;
    $class = NController::getClassName($name);
    if (NController::getIncludePath($name) !== $file) throw new RuntimeException('Controller override lookup failed');
    if (!class_exists($class)) throw new RuntimeException('Controller override autoload failed: ' . $class);
    if (realpath((new ReflectionClass($class))->getFileName()) !== realpath($file)) throw new RuntimeException('Wrong override loaded');
    if (!is_subclass_of($class, NController::class)) throw new RuntimeException('Controller inheritance changed');
    $checks++;
}
foreach (['Text','BodyImage','MediaElement'] as $class) {
    if (!class_exists($class)) throw new RuntimeException('Application model autoload failed: ' . $class);
    if (!is_subclass_of($class, NModel::class)) throw new RuntimeException('Model inheritance changed');
    $checks++;
}
if (session_status() !== PHP_SESSION_NONE) throw new RuntimeException('Loading started a session');
echo "PASS $checks installed core/override/inheritance/metadata checks; no site config or constructors\n";
