<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class QuestionController extends AbstractActionController
{
	public function indexAction() { print_r("Question Index"); exit; }
	
	public function listAction() {
		$printDataNum = 10;
		$query = $this->params()->fromQuery();
		$page = 1;
		if (isset($query["page"])) { $page = $query["page"]; }

		$inputDatas = array();

		$session = new Container("user");
		$userCode = $session->offsetGet("code");
		
		$questionDatas = array();

		$questionTable = $this->getServiceLocator()->get("QuestionTable");
		$totalQuestionDatas = array();
		if (isset($query["register"])) {
			$inputDatas["register"] = $query["register"];
			$registerCode = $query["register"];
			$totalQuestionDatas = iterator_to_array($questionTable->ReadByAdminCode_RegisterCode($userCode, $registerCode));
		}
		else {
			$totalQuestionDatas = iterator_to_array($questionTable->ReadByAdminCode($userCode));
		}

		$datas["questionDatas"] = array();
		if (!empty($totalQuestionDatas)) {
			$questionDatas = array();
			$startIdx = ($page - 1) * $printDataNum;
			$endIdx = ($page * $printDataNum);
			for ($i = 0; $startIdx + $i < $endIdx; $i++) {
				if (!isset($totalQuestionDatas[$startIdx + $i])) break;

				$questionDatas[$i] = $totalQuestionDatas[$startIdx + $i];
				$questionDatas[$i]["num"] = count($totalQuestionDatas) - ($startIdx + $i);
			}
			$datas["questionDatas"] = $questionDatas;
		}
		
		$paginationData["totalPage"] = count($totalQuestionDatas) / $printDataNum;
		$paginationData["currentPage"] = $page;
		$paginationData["url"] = "/admin/question/list/";
		$datas["paginationData"] = $paginationData;

		$datas["breadcrumbData"] = ["ITスキル診断問項管理"];

		$datas["inputDatas"] = $inputDatas;

		$this->layout("layout/list");
		return $this->SetViewModel($datas, "/question/question_list.phtml");
	}

	/** Make ViewModel with datas and template */
	function SetViewModel($datas, $template) {
		$vm = new ViewModel($datas);
		$vm->setTemplate($template);
		return $vm;
	}
}