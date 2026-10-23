<?php
// Render actual package picker templates for the optional Node callback check.
require getenv('NTERCHANGE_TEST_AUTOLOAD') ?: dirname(__DIR__) . '/vendor/autoload.php';
$compile = sys_get_temp_dir() . '/nterchange-picker-' . bin2hex(random_bytes(6));
mkdir($compile);
try {
    $smarty = new Smarty();
    $smarty->compile_dir = $compile;
    $smarty->assign('ckeditorfuncnum', '42');
    $smarty->assign('title', 'Package fixture');
    $smarty->assign('form', '<input id="bodyimage"><input id="mediaelement"><select name="pages"></select>');
    $html = [];
    foreach (['image_browse','media_browse'] as $template) {
        $html[$template] = $smarty->fetch(NTERCHANGE_DIR . '/app/views/ck_upload/' . $template . '.html');
    }
    echo json_encode($html, JSON_THROW_ON_ERROR);
} finally {
    foreach (glob($compile . '/*') as $file) unlink($file);
    rmdir($compile);
}
