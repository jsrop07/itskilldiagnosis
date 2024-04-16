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

		$datas = $this->GetOptionDatas([], []);

		$session = new Container("user");
		$userCode = $session->offsetGet("code");

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
	
	public function registAction() {
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "問題登録"];

		$datas["title"] = "問題登録";

		$optionTb = $this->getServiceLocator()->get("OptionTable");
		$optionDatas = iterator_to_array($optionTb->ReadAll());

		$session = new Container("user");
		$userCode = $session->offsetGet("code");

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		$questionData = $questionTb->ReadSaveByAdminCode($userCode);
		if (!empty($questionData)) {
			$datas["questionData"] = $questionData;
		}

		foreach ($optionDatas as $data) {
			if ($data["type"] != "status") {
				$index = $data["type"] . "Datas";
				$datas[$index] = array();
				$textDatas = explode(",", $data["texts"]);

				foreach ($textDatas as $text) {
					array_push($datas[$index], $text);
				}
			}
		}

		$adminTb = $this->getServiceLocator()->get("AdminTable");
		$datas["adminDatas"] = iterator_to_array($adminTb->ReadAll());

		$datas["approvalDatas"] = iterator_to_array($adminTb->ReadApprovers());

		$this->layout("layout/list");
		return $this->SetViewModel($datas, "/question/question_input.phtml");
	}

	public function confirmAction() {
		$post = $this->params()->fromPost();

		$answers = array();
		foreach ($post as $key => $value) {
			if (strpos($key, "answer") !== false) {
				array_push($answers, $value);
				unset($post[$key]);
			}
		}
		$post["answers"] = $answers;

		$datas["inputDatas"] = $post;
		$datas["printDatas"] = $post;

		$datas["inputDatas"]["answers"] = implode(",", $answers);

		$adminTb = $this->getServiceLocator()->get("AdminTable");
		$datas["printDatas"]["admin_create"] = $adminTb->ReadByCode($post["admin_create"])["name"];
		$datas["printDatas"]["admin_regist"] = $adminTb->ReadByCode($post["admin_regist"])["name"];
		

		$optionTb = $this->getServiceLocator()->get("OptionTable");
		$optionDatas = iterator_to_array($optionTb->ReadAll());

		foreach ($optionDatas as $data) {
			if ($data["type"] == "status") { continue; }
			
			$key = $data["type"];
			$textDatas = explode(",", $data["texts"]);
			
			$datas["printDatas"][$key] = $textDatas[$post[$key]];
		}
		$datas["title"] = "登録確認";
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "問題登録", "登録確認"];

		$this->layout("layout/list");
		return $this->SetViewModel($datas, "/question/question_confirm.phtml");
	}
	
	public function detailAction() {
		$route = $this->params()->fromRoute();
		$index = $route["index"];
		
		$questionTable = $this->getServiceLocator()->get("QuestionTable");
		$datas["questionData"] = iterator_to_array($questionTable->ReadByIndex($index));

		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "問題詳細"];

		$this->layout("layout/list");
		return $this->SetViewModel($datas, "/question/question_detail.phtml");
	}

	public function manageAction() {
		$printDataNum = 10;
		$query = $this->params()->fromQuery();
		$page = 1;
		if (isset($query["page"])) { $page = $query["page"]; }

		$route = $this->params()->fromRoute();

		$datas = array();

		$delete = false;
		if ($route["index"] == "delete") { $delete = true; }
		$datas["delete"] = $delete;

		$session = new Container("user");
		$userCode = $session["code"];
		$userLevel = $session["level"];
		
		$questionDatas = array();

		$questionTable = $this->getServiceLocator()->get("QuestionTable");
		$totalQuestionDatas = array();

		if ($delete) {
			if ($userLevel >= 2) { $totalQuestionDatas = iterator_to_array($questionTable->ReadQuestion()); }
			$totalQuestionDatas = iterator_to_array($questionTable->ReadByCreater_Register($userCode));
		}
		else {
			if ($userLevel >= 2) { $totalQuestionDatas = iterator_to_array($questionTable->ReadNotRegist()); }
			else { $totalQuestionDatas = iterator_to_array($questionTable->ReadNotRegistByRegister($userCode)); }
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
		$paginationData["url"] = "/admin/question/edit/regist";
		$datas["paginationData"] = $paginationData;

		$datas["breadcrumbData"] = ["ITスキル診断問項管理"];
		if ($delete) { array_push($datas["breadcrumbData"], "一括削除"); }
		else { array_push($datas["breadcrumbData"], "一括承認"); }

		$this->layout("layout/list");
		return $this->SetViewModel($datas, "/question/question_edit.phtml");
	}

	public function editAction() {
		$route = $this->params()->fromRoute();
		$idx = $route["index"];

		$datas = $this->GetOptionDatas([], ["status"]);

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		$datas["questionData"] = $questionTb->ReadByIndex($idx);
		$datas["mode"] = "edit";

		$adminTb = $this->getServiceLocator()->get("AdminTable");
		$datas["adminDatas"] = iterator_to_array($adminTb->ReadAll());
		$datas["approvalDatas"] = iterator_to_array($adminTb->ReadApprovers());

		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "問題詳細", "問題修正"];
		$datas["title"] = "問題修正";
		$this->layout("layout/list");
		return $this->SetViewModel($datas, "/question/question_input.phtml");
	}

	/** Make ViewModel with datas and template */
	function SetViewModel($datas, $template) {
		$vm = new ViewModel($datas);
		$vm->setTemplate($template);
		return $vm;
	}

	function GetOptionDatas($datas, $exceptArr) {
		$optionTb = $this->getServiceLocator()->get("OptionTable");
		$optionDatas = $optionTb->ReadAll();

		foreach ($optionDatas as $data) {
			if (in_array($data["type"], $exceptArr)) { continue; }
			
			$index = $data["type"] . "Datas";
			$textDatas = explode(",", $data["texts"]);
			$datas[$index] = $textDatas;			
		}

		return $datas;
	}

	public function createAction() {
		$post = $this->params()->fromPost();
		$post["status"] = 1;

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		if (isset($post["idx"])) {
			$questionTb->UpdateQuestion($post);
			die("success");
		}
		$questionTb->CreateQuestion($post);

		die("success");
	}

	public function saveAction() {
		$post = $this->params()->fromPost();
		$post["status"] = 0;

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		if (isset($post["idx"])) {
			$questionTb->UpdateQuestion($post);
			die("success");
		}

		$questionTb->CreateQuestion($post);
		die("success");
	}

	public function approveAction() {
		$post = $this->params()->fromPost();
		$idxDatas = explode(",", $post["idxs"]);

		$session = new Container("user");

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		if ($session["level"] >= 2) {
			foreach ($idxDatas as $idx) {
				$questionTb->UpdateToRegistByMaster_Idx($session["code"], $idx);
			}
			die("success");
		}

		foreach ($idxDatas as $idx) {
			$questionTb->UpdateToRegistByIdx($idx);
		}
		die("success");
	}

	public function updateAction() {
		$post = $this->params()->fromPost();
		$post["status"] = 0;

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		if (isset($post["idx"])) {
			$questionTb->UpdateQuestion($post);
			die("success");
		}

		$questionTb->CreateQuestion($post);
		die("success");
	}

	public function deleteAction() {
		$post = $this->params()->fromPost();
		$idxDatas = explode(",", $post["idxs"]);

		$questionTb = $this->getServiceLocator()->get("QuestionTable");

		foreach ($idxDatas as $idx) {
			$questionTb->DeleteQuestionByIdx($idx);
		}
		die("success");
	}
}