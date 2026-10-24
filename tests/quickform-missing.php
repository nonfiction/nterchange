<?php
// Missing fields must return the legacy error object, not throw a PHP 8 Error.
require getenv('NTERCHANGE_TEST_AUTOLOAD') ?: dirname(__DIR__) . '/vendor/autoload.php';
require_once NTERCHANGE_DIR . '/compat/pear/HTML/QuickForm.php';
$form = new HTML_QuickForm('missing_fields', 'post');
$checks = 0;
function same($expected, $actual) {
    global $checks;
    if ($expected !== $actual) throw new RuntimeException(var_export($expected, true) . ' != ' . var_export($actual, true));
    $checks++;
}
foreach (['getElement', 'removeElement'] as $method) {
    $error = $form->$method('parent_id');
    same(true, $error instanceof HTML_QuickForm_Error);
    same(true, PEAR::isError($error));
    same(QUICKFORM_NONEXIST_ELEMENT, $error->getCode());
    same('QuickForm Error: nonexistent html element', $error->getMessage());
    same(PEAR_ERROR_RETURN, $error->getMode());
    // A null mode uses the PEAR factory's default mode/options.
    same(E_USER_NOTICE, $error->level);
    same("Element 'parent_id' does not exist in HTML_QuickForm::$method()", $error->getUserInfo());
}
$invalid = new HTML_QuickForm_Error('invalid', PEAR_ERROR_RETURN, E_USER_WARNING, 'fixture details');
same(QUICKFORM_ERROR, $invalid->getCode());
same('QuickForm Error: Invalid error code: invalid', $invalid->getMessage());
same(PEAR_ERROR_RETURN, $invalid->getMode());
same(E_USER_WARNING, $invalid->level);
same('fixture details', $invalid->getUserInfo());
$form->addElement('text', 'title', 'Title');
same('text', $form->getElement('title')->getType());
same('text', $form->removeElement('title')->getType());
same(false, $form->elementExists('title'));
same(PHP_SESSION_NONE, session_status());
echo "PASS: $checks QuickForm missing/existing element error-contract checks\n";
