<?php
require_once 'n_model.php';
/**
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Page Template Containers Model
 * @author     Darron Froese <darron@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class PageTemplateContainers extends NModel {
	function __construct() {
		$this->__table = 'page_template_containers';
		$this->setHeadline('container_name');
		$this->form_elements['page_template_id'] = array('foreignkey', 'page_template_id', 'Page Template:', array('model'=>'page_template', 'headline'=>'template_name', 'addEmptyOption'=>false));
		parent::__construct();
	}
}
?>