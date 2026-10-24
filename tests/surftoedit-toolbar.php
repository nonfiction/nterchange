<?php
// Render the real toolbar and check its isolated layout and link-state contract.
require getenv('NTERCHANGE_TEST_AUTOLOAD') ?: dirname(__DIR__) . '/vendor/autoload.php';
$compile = sys_get_temp_dir() . '/nterchange-toolbar-' . bin2hex(random_bytes(6));
mkdir($compile, 0700);
$checks = 0;
function same($expected, $actual) {
    global $checks;
    if ($expected !== $actual) throw new RuntimeException(var_export($expected, true) . ' != ' . var_export($actual, true));
    $checks++;
}
try {
    $smarty = new Smarty();
    $smarty->compile_dir = $compile;
    $smarty->assign('id', 42);
    $html = $smarty->fetch(NTERCHANGE_DIR . '/app/views/page/surftoedit.html');
    same(true, str_contains($html, '<div class="page-edit">'));
    same(true, str_contains($html, '<div class="container">'));
    preg_match_all('/<a[^>]+href="([^"]+)"[^>]*>/', $html, $links);
    same([
        '/nterchange',
        '/nterchange/page/create?_referer=%2Fnterchange%2Fpage%2Fsurftoedit%2F42',
        '/nterchange/page/edit/42?_referer=%2Fnterchange%2Fpage%2Fsurftoedit%2F42',
        '/nterchange/site_admin/clear_all_cache?_referer=%2Fnterchange%2Fpage%2Fsurftoedit%2F42',
        '/nterchange/page/preview/42',
    ], $links[1]);
    foreach (['New Page', 'Edit Page', 'Clear Cache', 'View Page'] as $label) same(true, str_contains($html, '>' . $label . '</a>'));
    $css = file_get_contents(NTERCHANGE_DIR . '/nterchange/stylesheets/surftoedit.css');
    same(1, preg_match('/\.page-edit\s*\{[^}]*max-width:\s*100vw/s', $css));
    same(1, preg_match('/\.page-edit \.container\s*\{[^}]*width:\s*auto;[^}]*max-width:\s*980px/s', $css));
    foreach (['.page-edit a:visited', '.page-edit a:hover', '.page-edit a:focus', '.page-edit .surftoedit-white'] as $selector) same(true, str_contains($css, $selector));
    same(1, preg_match('/\.page-edit a:focus\s*\{[^}]*outline:\s*2px solid/s', $css));
    same(1, preg_match('/\.page-edit\s*\{[^}]*padding:\s*4px 0/s', $css));
    // WCAG luminance contrast for authored colors, not test-only color constants.
    preg_match('/\.page-edit\s*\{[^}]*background-color:\s*#([a-f0-9]{6})/s', $css, $background);
    $foregrounds = [];
    foreach (['visited', 'focus'] as $state) {
        same(1, preg_match('/\.page-edit a:' . $state . '\s*\{[^}]*color:\s*#([a-f0-9]{6})/s', $css, $match));
        $foregrounds[] = $match[1];
    }
    $luminance = function ($hex) {
        $rgb = array_map(fn($channel) => hexdec($channel) / 255, str_split($hex, 2));
        $rgb = array_map(fn($v) => $v <= 0.04045 ? $v / 12.92 : pow(($v + 0.055) / 1.055, 2.4), $rgb);
        return $rgb[0] * 0.2126 + $rgb[1] * 0.7152 + $rgb[2] * 0.0722;
    };
    foreach ($foregrounds as $foreground) same(true, ($luminance($background[1]) + 0.05) / ($luminance($foreground) + 0.05) >= 4.5);
    same(PHP_SESSION_NONE, session_status());
    echo "PASS: $checks rendered toolbar, URL, scoped CSS and contrast checks\n";
} finally {
    foreach (glob($compile . '/*') as $file) unlink($file);
    rmdir($compile);
}
