<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
class QuestionController extends AbstractActionController
{
	function ChkLogin() {
		$session = new Container("user");

		if (!isset($session["code"])) {
			echo "
				<script>
					alert('ログインしてくたさい。');
					self.location.href='/admin/login';
				</script>
			";
		}
	}

	public function indexAction() {
		$this->ChkLogin();
		header("Location: ./question/list");
		exit;
	}

	public function listAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断問項管理"];
		$datas["optionDatas"] = $this->GetOptionDatas();

		// Data of login user
		$session = new Container("user");
		$userLevel = $session["level"];

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		$query = $this->params()->fromQuery();

		// Get Current Page
		$page = 1;
		if (isset($query["page"])) {
			$page = $query["page"];
			unset($query["page"]);
		}

		// Set Search data
		$sqlWhere = array();
		if (isset($query["approver"])) {
			$adminTb = $this->getServiceLocator()->get("AdminTable");
			try { $sqlWhere["admin_approve"] = $adminTb->ReadByName($query["approver"])["code"]; }
			catch (\Exception $e) { print_r($e->getMessage()); exit; }
			$datas["searchDatas"]["approver"] = $query["approver"];
		}

		if (isset($query["title"])) {
			$sqlWhere["title"] = $query["title"];
			$datas["searchDatas"]["title"] = $query["title"];
		}

		// Set Align data
		$sqlOrder = array();
		if (isset($query["align"])) {
			$sqlOrder["field"] = explode("-", $query["align"])[0];
			$sqlOrder["seq"] = explode("-", $query["align"])[1];
			$datas["searchDatas"]["align"] = $query["align"];
		}

		$questionDatas = "";
		// Check User Level
		if ($userLevel >= 1) {
			$datas["totalNum"] = $questionTb->CountAllList();

			// Check Search data and Align data
			if (!empty($sqlOrder) && !empty($sqlWhere)) {
				try { $questionDatas = $questionTb->GetListBySearchnAlign($sqlWhere, $sqlOrder); }
				catch (\Exception $e) { print_r($e->getMessage()); exit; }
			}
			else if (!empty($sqlOrder) && empty($sqlWhere)) {
				try { $questionDatas = $questionTb->GetListByAlign($sqlOrder); }
				catch (\Exception $e) { print_r($e->getMessage()); exit; }
			}
			else if (empty($sqlOrder) && !empty($sqlWhere)) {
				try { $questionDatas = $questionTb->GetListBySearch($sqlWhere); }
				catch (\Exception $e) { print_r($e->getMessage()); exit; }
			}
			else {
				try { $questionDatas = $questionTb->GetAllList(); }
				catch (\Exception $e) { print_r($e->getMessage()); exit; }
			}
		} else {
			$userCode = $session["code"];

			$datas["totalNum"] = $questionTb->CountAllValid($userCode);

			if (!empty($sqlOrder) && !empty($sqlWhere)) {
				try { $questionDatas = $questionTb->GetListValidBySearchnAlign($userCode, $sqlWhere, $sqlOrder); }
				catch (\Exception $e) { print_r($e->getMessage()); exit; }
			}
			else if (!empty($sqlOrder) && empty($sqlWhere)) {
				try { $questionDatas = $questionTb->GetValidListByAlign($userCode, $sqlOrder); }
				catch (\Exception $e) { print_r($e->getMessage()); exit; }
			}
			else if (empty($sqlOrder) && !empty($sqlWhere)) {
				try { $questionDatas = $questionTb->GetValidListBySearch($userCode, $sqlWhere); }
				catch (\Exception $e) { print_r($e->getMessage()); exit; }
			}
			else {
				try { $questionDatas = $questionTb->GetValidList($userCode); }
				catch (\Exception $e) { print_r($e->getMessage()); exit; }
			}
		}

		$questionDatas->setCurrentPageNumber($page);
		$questionDatas->setItemCountPerPage(10);
		$datas["questionDatas"] = $questionDatas;
		return $this->SetViewModel($datas, "/question/question_list.phtml");
	}
	
	/** When you click 新規登録 button on 一覧 page */
	public function inputAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "問題登録"];
		$datas["title"] = "問題登録";
		$datas["optionDatas"] = $this->GetOptionDatasForInput();

		$adminTb = $this->getServiceLocator()->get("AdminTable");
		try { $datas["adminDatas"] = $adminTb->ReadAllList(); }
		catch (\Exception $e) { print_r($e->getMessage()); exit; }

		return $this->SetViewModel($datas, "/question/question_input.phtml");
	}

	/** When you click 登録 button on 問題登録 page */
	public function confirmAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "問題登録", "登録確認"];
		$datas["title"] = "登録確認";
		$datas["optionDatas"] = $this->GetOptionDatas();

		$post = $this->params()->fromPost();
		$datas["questionData"] = $post;

		// Save register name
		$adminTb = $this->getServiceLocator()->get("AdminTable");
		try { $datas["register"] = $adminTb->ReadByCode($post["admin_regist"])["name"]; }
		catch (\Exception $e) { print_r($e->getMessage()); exit; }

		return $this->SetViewModel($datas, "/question/question_confirm.phtml");
	}
	
	/** When you choose list data on 問題一覧 page */
	public function detailAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "問題詳細"];		
		$datas["optionDatas"] = $this->GetOptionDatas();
	
		$index = $this->params()->fromRoute("index");

		$questionTable = $this->getServiceLocator()->get("QuestionTable");
		$questionData = array();
		try { $questionData = $questionTable->ReadByIdx($index); }
		catch (\Exception $e) { print_r($e->getMessage()); exit; }
		$datas["questionData"] = $questionData;

		// Save register name
		$adminTb = $this->getServiceLocator()->get("AdminTable");
		try { $datas["register"] = $adminTb->ReadByCode($questionData["admin_regist"])["name"]; }
		catch (\Exception $e) { print_r($e->getMessage()); exit; }

		// Save approver name
		if ($questionData["date_approve"] != null) {
			try { $datas["approver"] = $adminTb->ReadByCode($questionData["admin_approve"])["name"]; }
			catch (\Exception $e) { print_r($e->getMessage()); exit; }
		}

		// Save Note
		$datas["note"] = str_replace("\n", "<br/>", $datas["questionData"]["note"]);

		return $this->SetViewModel($datas, "/question/question_detail.phtml");
	}

	/** When you click 修正 button on 問題詳細 page */
	public function editAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "問題詳細", "問題修正"];
		$datas["title"] = "問題修正";
		$datas["optionDatas"] = $this->GetOptionDatasForInput();

		$optionTb = $this->getServiceLocator()->get("OptionTable");
		try { $datas["updateStatus"] = $optionTb->ReadByText("承認依頼")["idx"]; }
		catch (\Exception $e) { print_r($e->getMessage()); exit; }
		
		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		$idx = $this->params()->fromRoute("index");
		try { $datas["questionData"] = $questionTb->ReadByIdx($idx); }
		catch (\Exception $e) { print_r($e->getMessage()); exit; }

		$adminTb = $this->getServiceLocator()->get("AdminTable");
		try { $datas["adminDatas"] = $adminTb->ReadAll(); }
		catch (\Exception $e) { print_r($e->getMessage()); exit; }

		return $this->SetViewModel($datas, "/question/question_input.phtml");
	}

	public function csvAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "CSV登録"];
		return $this->SetViewModel($datas, "/question/question_csv.phtml");
	}

	/** Set Layout & Make ViewModel with datas and template
	 * @param mixed $datas array #ViewModel($datas)
	 * @param mixed $template string #setTemplate($template)
	 * @return ViewModel
	*/
	function SetViewModel($datas, $template) {
		$this->layout("layout/default");
		$vm = new ViewModel($datas);
		$vm->setTemplate($template);
		return $vm;
	}

	/** Get optionDatas
	 * @return array $optionDatas ["idx" => "text"]
	*/
	function GetOptionDatas() {
		$beforeOptionDatas = array();
		$optionTb = $this->getServiceLocator()->get("OptionTable");
		try { $beforeOptionDatas = $optionTb->ReadAll(); }
		catch (\Exception $e) { print_r($e->getMessage()); exit; }

		$optionDatas = array();
		foreach ($beforeOptionDatas as $data) {
			$optionDatas[$data["idx"]] = $data["text"];
		}

		return $optionDatas;
	}

	/** Get optionDatas for Input
	 * @return array $optionDatas ["idx" => $data]
	*/
	function GetOptionDatasForInput() {
		$beforeOptionDatas = array();
		$optionTb = $this->getServiceLocator()->get("OptionTable");
		try { $beforeOptionDatas = $optionTb->ReadValid(); }
		catch (\Exception $e) { print_r($e->getMessage()); exit; }

		$optionDatas = array();
		$class2ndDatas = array();
		$other = array();
		foreach ($beforeOptionDatas as $data) {
			if ($data["type"] == "status") { continue; }
			if ($data["type"] == "level") { continue; }
			if ($data["text"] == "その他") {
				$other = $data;
				continue;
			}
			if ($data["type"] == "class2nd") {
				array_push($class2ndDatas, $data);
				continue;
			}

			if (!isset($optionDatas[$data["type"]])) {
				$optionDatas[$data["type"]] = array();
			}
			array_push($optionDatas[$data["type"]], $data);
		}

		array_push($optionDatas["class1st"], $other);

		foreach ($class2ndDatas as $data) {
			if (!isset($optionDatas["class2nd"][$data["class_upper"]])) {
				$optionDatas["class2nd"][$data["class_upper"]] = array();
			}
			array_push($optionDatas["class2nd"][$data["class_upper"]], $data);
		}

		return $optionDatas;
	}

	public function registAction() {
		$post = $this->params()->fromPost();

		$optionTb = $this->getServiceLocator()->get("OptionTable");
		try { $post["status"] = $optionTb->ReadByText("新規")["idx"]; }
		catch (\Exception $e) { die($e->getMessage()); }

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		try { $questionTb->CreateQuestion($post); }
		catch (\Exception $e) { die($e->getMessage()); }

		die("success");
	}

	public function updateAction() {
		$post = $this->params()->fromPost();
		$idx = ["idx" => $post["idx"]];
		unset($post["idx"]);

		$post["admin_approve"] = null;
		$post["date_approve"] = null;

		$optionTb = $this->getServiceLocator()->get("OptionTable");
		try { $post["status"] = $optionTb->ReadByText("承認依頼")["idx"]; }
		catch (\Exception $e) { die($e->getMessage()); }

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		try { $questionTb->UpdateByIdx($idx, $post); }
		catch (\Exception $e) { die($e->getMessage()); }

		die("success");
	}

	public function removeAction() {
		$post = $this->params()->fromPost();
		$idxDatas = explode(",", $post["idxs"]);

		$session = new Container("user");
		$sqlSet["admin_delete"] = $session["code"];

		$optionTb = $this->getServiceLocator()->get("OptionTable");
		try { $sqlSet["status"] = $optionTb->ReadByText("削除")["idx"]; }
		catch (\Exception $e) { die($e->getMessage()); }

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		foreach ($idxDatas as $idx) {
			try { $questionTb->RemoveQuestion($idx, $sqlSet); }
			catch (\Exception $e) { die($e->getMessage()); }
		}
		die("success");
	}

	public function approveAction() {
		$idxs = $this->params()->fromPost("idxs");
		$idxDatas = explode(",", $idxs);

		$session = new Container("user");

		$questionTb = $this->getServiceLocator()->get("QuestionTable");

		$beforeNotes = array();
		foreach ($idxDatas as $idx) {
			try { $result = $questionTb->ReadByIdx($idx); }
			catch (\Exception $e) { die($e->getMessage()); }
			if ($result["date_approve"] != null) { die("fail"); }
			else { array_push($beforeNotes, $result["note"]); }
		}
		
		$log = "承認　" . date("Y.m.d") . "　" . $session["name"] . "\n";

		$sqlSet["admin_approve"] = $session["code"];
		$sqlSet["date_approve"] = date("Y-m-d H:i:s");

		$optionTb = $this->getServiceLocator()->get("OptionTable");
		try { $sqlSet["status"] = $optionTb->ReadByText("承認済")["idx"]; }
		catch (\Exception $e) { die($e->getMessage()); }

		foreach ($idxDatas as $index => $idx) {
			$sqlSet["note"] = $beforeNotes[$index] . $log;
			
			try { $questionTb->UpdateByIdx($idx, $sqlSet); }
			catch (\Exception $e) { die($e->getMessage()); }
		}

		die("success");
	}

	public function createByCsvAction() {
		if ($_FILES["csv_file"]["error"] == "0") {
			header("Content-Type: text/html; charset=utf-8");

			$filePointer = fopen($_FILES["csv_file"]["tmp_name"], "r");
			if (!$filePointer) { die("ファイル　オープン　失敗"); }

			$csvStrings = array();
			while($line = fgetcsv($filePointer, 1024, ",")) {
				array_push($csvStrings, $line);
			}

			$keys = $csvStrings[0];
			unset($csvStrings[0]);

			$questionTb = $this->getServiceLocator()->get("QuestionTable");
			$optionTb = $this->getServiceLocator()->get("OptionTable");

			$questionData = array();
			foreach ($csvStrings as $csvDatas) {
				foreach ($csvDatas as $idx => $data) {
					$questionData[$keys[$idx]] = $data;
				}

				for ($i = 4; $i <= 5; $i++) {
					if (!isset($questionData["answer" . $i]) || trim($questionData["answer" . $i]) == "") {
						$questionData["answer" . $i] = null;
					}
				}

				switch($questionData["level"]) {
					case "0級":
						$questionData["level"] = 0;
						break;
					case "初級":
						$questionData["level"] = 1;
						break;
					case "中級":
						$questionData["level"] = 2;
						break;
					case "高級":
						$questionData["level"] = 3;
						break;
				}

				$session = new Container("user");
				try {
					$questionData["class1st"] = $optionTb->ReadByText([$questionData["class1st"]])["idx"];
					$sqlWhere["type"] = "class2nd";
					$sqlWhere["text"] = $questionData["class2nd"];
					$sqlWhere["class_upper"] = $questionData["class1st"];
					$questionData["class2nd"] = $optionTb->ReadByOption($sqlWhere)[0]["idx"];
					$questionData["type"] = $optionTb->ReadByText([$questionData["type"]])["idx"];
					$questionData["note"] = "CSVで作成　" . date("Y.m.d") . "　" . $session["name"] . "\n";
					$questionData["status"] = $optionTb->ReadByText(["新規"])["idx"];
					$questionData["admin_regist"] = $session["code"];
					$questionData["date_regist"] = date("Y-m-d H:i:s");
					
					$questionTb->CreateQuestion($questionData);
				} catch (\Exception $e) {
					die($e->getMessage());
				}
			}
		}

		die("success");
	}

	public function sortArrByKey($arr, $alignData) {
		$optionTb = $this->getServiceLocator()->get("OptionTable");

		$key = explode("_", $alignData)[0];
		$align = explode("_", $alignData)[1];

		$tempArr = array();
		if ($key == "regist") {
			$key = "date_regist";
			foreach ($arr as $idx => $data) {
				$tempArr[$idx] = $data[$key];
			}
		} else {
			foreach ($arr as $idx => $data) {
				$data[$key] = $optionTb->ReadOption(["idx" => $data[$key]])["text"];
				$arr[$idx][$key] = $data[$key];
				$tempArr[$idx] = $data[$key];
			}
		}

		if ($align == "ASC") { array_multisort($tempArr, SORT_ASC, $arr); }
		else { array_multisort($tempArr, SORT_DESC, $arr); }
		unset($tempArr);

		if ($key == "date_regist") { return $arr; }

		foreach ($arr as $idx => $data) {
			$arr[$idx][$key] = $optionTb->ReadOption(["text" => $data[$key]])["idx"];
		}

		return $arr;
	}
}