<?php
// Local library regression checks. No database, HTTP requests or mail delivery.
require getenv('NTERCHANGE_TEST_AUTOLOAD') ?: dirname(__DIR__) . '/vendor/autoload.php';
require 'Mail/mime.php';
require 'Mail.php';

function check($condition, $label) {
    if (!$condition) {
        throw new RuntimeException($label);
    }
    echo "PASS $label\n";
}

$_SERVER['PHP_SELF'] = '/package-pagination-fixture';
$_SESSION = array();
SmartyPaginate::connect('fixture', array());
SmartyPaginate::setLimit(25, 'fixture');
SmartyPaginate::setTotal(51, 'fixture');
SmartyPaginate::connect('fixture', array('next' => 26));
check(SmartyPaginate::getCurrentIndex('fixture') === 25 &&
    SmartyPaginate::getLastItem('fixture') === 50, 'pagination middle page');
check(SmartyPaginate::_getPrevPageItem('fixture') === 1 &&
    SmartyPaginate::_getNextPageItem('fixture') === 51, 'pagination previous/next');
SmartyPaginate::connect('fixture', array('next' => 51));
check(SmartyPaginate::getLastItem('fixture') === 51 &&
    SmartyPaginate::_getNextPageItem('fixture') === false, 'pagination last partial page');
SmartyPaginate::connect('fixture', array('next' => 999));
check(SmartyPaginate::getCurrentItem('fixture') === 51, 'out-of-range page retains current page');
SmartyPaginate::connect('other', array());
SmartyPaginate::setTotal(0, 'other');
check(SmartyPaginate::getCurrentItem('other') === 1 &&
    SmartyPaginate::getCurrentItem('fixture') === 51, 'independent session pagination IDs');
$smarty = new Smarty();
SmartyPaginate::assign($smarty, 'fixture_pages', 'fixture');
$pages = $smarty->get_template_vars('fixture_pages');
check($pages['total'] === 51 && $pages['page'][3]['is_current'], 'real Smarty pagination assignment');
SmartyPaginate::disconnect('fixture');
check(!SmartyPaginate::isConnected('fixture') && SmartyPaginate::isConnected('other'), 'disconnect one ID');
SmartyPaginate::disconnect();
check(!SmartyPaginate::isConnected('other'), 'disconnect all IDs');

$mime = new Mail_mime("\r\n");
$mime->setTXTBody("Plain café Ω\r\nTrailing space ");
$mime->setHTMLBody('<p>HTML café Ω</p>');
$attachment = "Package fixture\0binary\xff";
$file = tempnam(sys_get_temp_dir(), 'package-mime-fixture-');
try {
    file_put_contents($file, $attachment);
    check($mime->addAttachment($file, 'application/octet-stream', 'café.bin', true,
        'base64', 'attachment', 'UTF-8') === true, 'file attachment read');
} finally {
    unlink($file);
}
$body = $mime->get(array('text_charset' => 'UTF-8', 'html_charset' => 'UTF-8',
    'head_charset' => 'UTF-8', 'text_encoding' => 'quoted-printable'));
$headers = $mime->headers(array('From' => 'package-fixture@example.invalid', 'Subject' => 'Café Ω'));
check(str_contains($headers['Content-Type'], 'multipart/mixed') &&
    str_contains($body, 'multipart/alternative'), 'multipart text/HTML/attachment structure');
check(str_contains($body, base64_encode($attachment)) &&
    str_contains($body, 'caf%C3%A9.bin'), 'attachment bytes and UTF-8 filename');
check(str_contains(quoted_printable_decode($body), 'Plain café Ω') &&
    str_contains(quoted_printable_decode($body), '<p>HTML café Ω</p>'), 'UTF-8 body roundtrip');
check(iconv_mime_decode($headers['Subject'], 0, 'UTF-8') === 'Café Ω', 'encoded subject roundtrip');
$manual = $mime->_encodeHeaders(array('Subject' => '"Café Ω"'), array('ignore-iconv' => true));
check(iconv_mime_decode($manual['Subject'], 0, 'UTF-8') === '"Café Ω"', 'quoted-printable callback without iconv encoder');
check(PEAR::isError($mime->addAttachment('/no/package-mime-fixture-file.bin')), 'missing attachment returns PEAR error');
check(PEAR::isError(Mail::factory('package_missing_fixture_driver')), 'missing driver returns PEAR error');
$mail = Mail::factory('mail');
check($mail instanceof Mail_mail && $mail->sep === PHP_EOL, 'Mail factory runs driver constructor');
check(PEAR::isError($mail->prepareHeaders(array('From' => 'invalid@@example.invalid'))), 'invalid From returns PEAR error');
