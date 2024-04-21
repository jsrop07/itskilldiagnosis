<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class QuestionController extends AbstractActionController
{
	public function indexAction() { print_r("Question Index"); exit; }
	
	public function listAction() {
		$datas["breadcrumbData"] = ["ITスキル診断問項管理"];
		$query = $this->params()->fromQuery();
		$page = 1;
		if (isset($query["page"])) {
			$page = $query["page"];
			unset($query["page"]);
		}

		$printDataNum = 10;

		$session = new Container("user");
		$userCode = $session["code"];
		$userLevel = $session["level"];

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		$totalQuestionDatas = array();
		if (!empty($query)) {
			foreach ($query as $key => $value) {
				$datas["inputDatas"][$key] = $value;
			}

			if (is_numeric(substr($query["register"], 0, 2)) && is_numeric(substr($query["register"], 2, 7))) {
				$query["admin_regist"] = $query["register"];
			}
			else {
				$adminTb = $this->getServiceLocator()->get("AdminTable");
				$query["admin_regist"] = $adminTb->ReadByName($query["register"])["code"];
			}

			unset($query["register"]);

			if ($userLevel >= 1) { $totalQuestionDatas = iterator_to_array($questionTb->ReadListByOption($query)); }
			else { $totalQuestionDatas = iterator_to_array($questionTb->ReadListByCode_Option($userCode, $query)); }
		}
		else {
			if ($userLevel >= 1) { $totalQuestionDatas = iterator_to_array($questionTb->ReadAllList()); }
			else { $totalQuestionDatas = iterator_to_array($questionTb->ReadListByRegist($userCode)); }
		}


		if (!empty($totalQuestionDatas)) {
			$PrintQuestionDatas = array();
			$startIdx = ($page - 1) * $printDataNum;
			$endIdx = ($page * $printDataNum);
			
			for ($i = 0; $startIdx + $i < $endIdx; $i++) {
				if (!isset($totalQuestionDatas[$startIdx + $i])) break;

				$PrintQuestionDatas[$i] = $totalQuestionDatas[$startIdx + $i];
				$PrintQuestionDatas[$i]["num"] = count($totalQuestionDatas) - ($startIdx + $i);
			}
			
			$datas["questionDatas"] = $PrintQuestionDatas;
		}
		
		$totalPage = ceil(count($totalQuestionDatas) / $printDataNum);
		if ($totalPage < 2) { $totalPage = 0; }
		$paginationData["totalPage"] = $totalPage;
		$paginationData["currentPage"] = $page;
		$paginationData["url"] = "/admin/question/list/";
		$datas["paginationData"] = $paginationData;
		
		$pagenum=(int) $this->params()->fromQuery("page", 1);
		$pagecount=10;

		$datas = $this->GetOptionDatas($datas);

		$this->layout("layout/list");
		$vm = $this->SetViewModel($datas, "/question/question_list.phtml");
		$vm->noticelist=$questionTb->getNoticeList($query);
		$vm->noticelist->setCurrentPageNumber($pagenum);
		$vm->noticelist->setItemCountPerPage($pagecount);
		return $vm;
	}
	
	public function registAction() {
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "問題登録"];
		$datas["title"] = "問題登録";

		$adminTb = $this->getServiceLocator()->get("AdminTable");
		$datas["adminDatas"] = iterator_to_array($adminTb->ReadAll());

		$optionTb = $this->getServiceLocator()->get("OptionTable");
		$optionDatas = iterator_to_array($optionTb->ReadValid());

		$datas["optionDatas"] = array();
		$other = array();
		foreach ($optionDatas as $data) {
			if ($data["type"] == "status") { continue; }
			if ($data["text"] == "その他") {
				$other = $data;
				continue;
			}

			if (!isset($datas["optionDatas"][$data["type"]])) {
				$datas["optionDatas"][$data["type"]] = array();
			}
			array_push($datas["optionDatas"][$data["type"]], $data);
		}
		
		array_push($datas["optionDatas"]["class1st"], $other);

		$this->layout("layout/list");
		return $this->SetViewModel($datas, "/question/question_input.phtml");
	}

	public function confirmAction() {
		$datas["title"] = "登録確認";
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "問題登録", "登録確認"];

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
		$datas["printDatas"]["register"] = $adminTb->ReadByCode($post["admin_regist"])["name"];
			
		$optionTb = $this->getServiceLocator()->get("OptionTable");
		$optionDatas = iterator_to_array($optionTb->ReadAll());

		foreach ($optionDatas as $data) {
			$datas["optionDatas"][$data["idx"]] = $data["text"];
		}

		$this->layout("layout/list");
		return $this->SetViewModel($datas, "/question/question_confirm.phtml");
	}
	
	public function detailAction() {
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "問題詳細"];
		$route = $this->params()->fromRoute();
		$index = $route["index"];
		
		$questionTable = $this->getServiceLocator()->get("QuestionTable");
		$questionData = iterator_to_array($questionTable->ReadByIndex($index));
		$datas["questionData"] = $questionData;
		
		$adminTb = $this->getServiceLocator()->get("AdminTable");
		$datas["register"] = $adminTb->ReadByCode($questionData["admin_regist"])["name"];

		if ($questionData["date_approve"] != null) {
			$datas["approver"] = $adminTb->ReadByCode($questionData["admin_approve"])["name"];
		}


		$datas = $this->GetOptionDatas($datas);

		$this->layout("layout/list");
		return $this->SetViewModel($datas, "/question/question_detail.phtml");
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

	public function csvAction() {
		$this->layout("layout/list");
		return $this->SetViewModel([], "/question/question_csv.phtml");
	}

	/** Make ViewModel with datas and template */
	function SetViewModel($datas, $template) {
		$vm = new ViewModel($datas);
		$vm->setTemplate($template);
		return $vm;
	}

	function GetOptionDatas($datas) {
		$optionTb = $this->getServiceLocator()->get("OptionTable");
		$optionDatas = iterator_to_array($optionTb->ReadAll());

		foreach ($optionDatas as $data) {
			$datas["optionDatas"][$data["idx"]] = $data["text"];
		}

		return $datas;
	}

	public function createAction() {
		$post = $this->params()->fromPost();

		$optionTb = $this->getServiceLocator()->get("OptionTable");
		$post["status"] = $optionTb->ReadByText("新規")["idx"];

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		if (isset($post["idx"])) {
			$questionTb->UpdateQuestion($post);
			die("success");
		}
		$questionTb->CreateQuestion($post);

		die("success");
	}

	public function chkapproveAction() {
		$post = $this->params()->fromPost();
		$idxDatas = explode(",", $post["idxs"]);

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		foreach ($idxDatas as $idx) {
			$result = $questionTb->ReadByIdx($idx);
			if ($result["date_approve"] != null) { die("fail"); }
		}
		die("success");
	}

	public function approveAction() {
		$post = $this->params()->fromPost();
		$idxDatas = explode(",", $post["idxs"]);

		$session = new Container("user");

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		foreach ($idxDatas as $idx) {
			$questionTb->UpdateToRegistByIdx($idx, $session["code"]);
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

	public function savecsvAction() {
		$post = $this->params()->fromPost();
		$csvStrings = explode("\n", $post["csv"]);

		$csvStrings[0] = str_replace("\r", "", $csvStrings[0]);
		$csvKeys = explode(",", $csvStrings[0]);
		unset($csvStrings[0]);

		$csvDatas = array();

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		
		foreach ($csvStrings as $i => $string) {
			$string = str_replace("\r", "", $string);
			$keyIndex = 0;
			while ($string != "") {
			$value = "";

				if ($string[0] == "\"") {
					$index = strpos($string, "\",");
					$value = substr($string, 1, $index - 1);
					$string = substr($string, $index + 2, strlen($string) - $index);
				}
				else {
					$index = strpos($string, ",");
					$value = substr($string, 0, $index);
					$string = substr($string, $index + 1, strlen($string) - $index);
				}

				$csvDatas[$i - 1][$csvKeys[$keyIndex]] = $value;
				$keyIndex++;
			}

			if (!array_key_exists("admin_create", $csvDatas[$i - 1])) {
				$session = new Container("user");
				$csvDatas[$i - 1]["admin_create"] = $session("code");
			}

			if (!array_key_exists("date_create", $csvDatas[$i - 1])) {
				$csvDatas[$i - 1]["date_create"] = date("Y-m-d H:i:s");
			}

			if (!array_key_exists("status", $csvDatas[$i - 1])) {
				$csvDatas[$i - 1]["status"] = 0;
			}


			$questionTb->CreateQuestion($csvDatas[$i -1]);
		}

		die(print_r($csvDatas[0]));
	}
}