<?php
// Library-only date checks: no site bootstrap, sessions, database or network.
if (!isset($argv[1])) {
    foreach (['MST7MDT', 'Etc/GMT+6'] as $zone) {
        passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . escapeshellarg($zone), $status);
        if ($status !== 0) exit($status);
    }
    exit;
}
require getenv('NTERCHANGE_TEST_AUTOLOAD') ?: dirname(__DIR__) . '/vendor/autoload.php';
define('SITE_TIME_ZONE', $argv[1]);
NConfig::setTimezone(SITE_TIME_ZONE);
$dates = ['2026-01-23 09:17:00', '2026-07-23 09:17:00', '2026-10-23 09:17:00', '2026-12-23 09:17:00'];
if (SITE_TIME_ZONE === 'Etc/GMT+6') {
    $dates = array_merge($dates, ['2026-03-08 02:30:00', '2026-11-01 01:30:00', '2026-12-31 23:17:00', '2028-02-29 09:17:00']);
}
$checks = 0;
foreach ($dates as $local) {
    $native = new DateTimeImmutable($local, new DateTimeZone(SITE_TIME_ZONE));
    $utc = $native->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    $actual = NDate::convertTimeToUTC($local, '%Y-%m-%d %H:%M:%S');
    if ($actual !== $utc) throw new RuntimeException("$local in " . SITE_TIME_ZONE . ": expected $utc, got $actual");
    $checks++;
    if (NDate::convertTimeToClient($utc, '%Y-%m-%d %H:%M:%S') !== $local) throw new RuntimeException('Date round-trip changed local time');
    $checks++;
    if (SITE_TIME_ZONE === 'Etc/GMT+6') {
        if ($native->getOffset() !== -21600 || NDate::getClientTZ()->hasDaylightTime()) throw new RuntimeException('Fixed UTC-06:00 policy changed');
        $checks++;
    }
}
if (session_status() !== PHP_SESSION_NONE) throw new RuntimeException('Date checks opened a session');
echo SITE_TIME_ZONE . ": $checks timezone checks passed\n";
