<?php
// Composer loads the runtime without starting sessions or configuring a site.
define('NTERCHANGE_DIR', __DIR__);
define('NTERCHANGE_VERSION', '5.0.0');
if (!defined('SMARTY_DIR')) define('SMARTY_DIR', __DIR__ . '/vendor/Smarty/libs/');
if (!defined('PEAR_DIR')) define('PEAR_DIR', __DIR__ . '/compat/pear');

set_include_path(get_include_path() . PATH_SEPARATOR . implode(PATH_SEPARATOR, [
    __DIR__, __DIR__ . '/lib', __DIR__ . '/lib/view', __DIR__ . '/app/controllers',
    __DIR__ . '/vendor', __DIR__ . '/compat/pear',
]));
require_once __DIR__ . '/vendor/Smarty/libs/Smarty.class.php';
require_once __DIR__ . '/vendor/SmartyPaginate/SmartyPaginate.class.php';
