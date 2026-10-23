<?php
// Real controller/widget with a namespaced clock and fake auth/query/output.
namespace NterchangeAuditYearTest;
require getenv('NTERCHANGE_TEST_AUTOLOAD') ?: dirname(__DIR__) . '/vendor/autoload.php';
define('CURRENT_SITE', 'public');
function date($format, $timestamp = null) {
    return \date($format, $timestamp ?? \mktime(12, 0, 0, 10, 23, $GLOBALS['auditYear']));
}
class NAuth {}
#[\AllowDynamicProperties]
class AdminController {
    public $values = [];
    private $model;
    function __construct() {
        $this->model = new class {
            function quote($value) { return "'$value'"; }
            function find($options) { return false; }
        };
    }
    function getParam($name) { return $GLOBALS['auditDate']; }
    function &loadModel($name) { return $this->model; }
    function set($name, $value) { $this->values[$name] = $value; }
    function loadSubnav($parameter) {}
    function render($options) {}
}
$source = file_get_contents(NTERCHANGE_DIR . '/app/controllers/audit_trail_controller.php');
eval('namespace NterchangeAuditYearTest; use \\NQuickForm; use \\NDate; use \\NConfig; use \\HTML_QuickForm_Renderer_Array;' . substr($source, 5));
class PickerController extends AuditTrailController {
    function checkRSSFeed() {}
}
$checks = 0;
$currentYear = (int) \date('Y');
foreach ([$currentYear, $currentYear+1, $currentYear+10] as $year) {
    $GLOBALS['auditYear'] = $year;
    foreach ([2001, 2018, $year-5] as $minimumYear) {
      \NConfig::$auditTrailMinYear = $minimumYear;
      foreach ([$year, $minimumYear] as $selectedYear) {
        $GLOBALS['auditDate'] = ['Y'=>$selectedYear, 'F'=>10, 'd'=>23];
        $controller = new PickerController();
        $controller->viewlist();
        $html = $controller->values['audit_search']['elements'][0]['html'];
        preg_match('/<select\b[^>]*name="date\[Y\]"[^>]*>(.*?)<\/select>/s', $html, $select);
        preg_match_all('/<option\b[^>]*value="([^"]*)"/', $select[1], $options);
        if ($options[1] !== array_merge([''], array_map('strval', range($minimumYear, $year+1)))) throw new \RuntimeException('Year range or empty option changed');
        if (!preg_match('/<option\b(?=[^>]*value="'.$selectedYear.'")(?=[^>]*selected="selected")/', $select[1])) throw new \RuntimeException('Selected year missing');
        foreach (['F'=>10, 'd'=>23] as $part=>$value) {
            preg_match('/<select\b[^>]*name="date\['.$part.'\]"[^>]*>(.*?)<\/select>/s', $html, $partSelect);
            if (!preg_match('/<option\b(?=[^>]*value="'.$value.'")(?=[^>]*selected="selected")/', $partSelect[1])) throw new \RuntimeException('Month/day changed');
        }
        $checks++;
      }
    }
}
echo "PASS $checks actual controller/widget cases with fake auth/query and simulated rollover\n";
