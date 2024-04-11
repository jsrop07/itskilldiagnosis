<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class QuestionController extends AbstractActionController
{
	public function indexAction() { print_r("Question Index"); exit; }
	
	public function listAction() {
		$this->layout("layout/list");

		$paginationData["totalPage"] = 10;
		$paginationData["currentPage"] = 3;
		$paginationData["url"] = "/admin/question/list/";
		$datas["paginationData"] = $paginationData;

		$datas["breadcrumbData"] = ["ITスキル診断問項管理"];

		return $this->SetViewModel($datas, "/question/question_list.phtml");
	}

	/** Make ViewModel with datas and template */
	function SetViewModel($datas, $template) {
		$vm = new ViewModel($datas);
		$vm->setTemplate($template);
		return $vm;
	}
}