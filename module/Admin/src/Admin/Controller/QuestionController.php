<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Admin\Model\LogModule;

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

		$optionTb = $this->getServiceLocator()->get("OptionTable");
		$selectValueDatas = $this->GetOptionDatasForInput();
		$selectValueDatas["status"][] = $optionTb->ReadByText("新規");
		$selectValueDatas["status"][] = $optionTb->ReadByText("承認依頼");
		$selectValueDatas["status"][] = $optionTb->ReadByText("承認済");
		$datas["selectValueDatas"] = $selectValueDatas;

		// Data of login user
		$session = new Container("user");
		$userLevel = $session["level"];

		/*
			作成：朴昰成
			作成日：24/06/19
		*/
		$LogModule = new LogModule();
		/* ここまで */
		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		$query = $this->params()->fromQuery();

		// Get Current Page
		$page = 1;
		if (isset($query["page"])) {
			$page = $query["page"];
			/*
				作成：朴昰成
				削除：朴昰成
				削除日：24/06/19
			*/

			/* 削除前：
			unset($query["page"]);
			*/
		}

		// Set Search data
		$sqlWhere = array();
		/*
			作成：朴昰成
			削除：朴昰成
			削除日：24/06/19
		*/

		/* 削除前：
		if (isset($query["approver"])) {
			$adminTb = $this->getServiceLocator()->get("AdminTable");
			try { $sqlWhere["admin_approve"] = $adminTb->ReadByName($query["approver"])["code"]; }
			catch (\Exception $e) { print_r($e->getMessage()); exit; }
			$datas["searchDatas"]["approver"] = $query["approver"];
		}
		*/

		if (isset($query["title"])) {
			$sqlWhere["title"] = $query["title"];
			$datas["searchDatas"]["title"] = $query["title"];
		}

		if (isset($query["select"])) {
			$selectData = explode("-", $query["select"]);

			if (isset($selectData[2])) {
				$sqlWhere["class2nd"] = $selectData[2];
			}
			else if (isset($selectData[1])) {
				if ($selectData[0] == "class") { $sqlWhere["class1st"] = $selectData[1]; }
				else { $sqlWhere[$selectData[0]] = $selectData[1]; }
			}
		}

		$questionDatas = "";
		// Check User Level
		/*
			作成：朴昰成
			修正：朴昰成
			修正日：24/06/19
		*/

		/* 修正前：
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
		*/

		/* 修正後： */
		if ($userLevel >= 1) {
			try { 
				$datas["totalNum"] = $questionTb->CountAllValid();
			} catch (\Exception $e) {
				$logData["reason"] = "exception at QuestionController listAction QuestionTable CountAllList";
				$logData["message"] = $e->getMessage();
				$log = $LogModule->SaveLog($logData);
				print_r($log);
				exit;
			}

			// Check Search data and Align data
			if (empty($sqlWhere)) {
				try { 
					$questionDatas = $questionTb->GetAllList();
				} catch (\Exception $e) {
					$logData["reason"] = "exception at QuestionController listAction QuestionTable GetAllList";
					$logData["message"] = $e->getMessage();
					$log = $LogModule->SaveLog($logData);
					print_r($log);
					exit;
				}
			} else {
				try { 
					$questionDatas = $questionTb->GetListBySearch($sqlWhere);
				} catch (\Exception $e) {
					$logData["reason"] = "exception at QuestionController listAction QuestionTable GetListBySearch";
					$logData["message"] = $e->getMessage();
					$log = $LogModule->SaveLog($logData);
					print_r($log);
					exit;
				}
			}
		} else {
			$userCode = $session["code"];

			try { 
				$datas["totalNum"] = $questionTb->CountAllValid($userCode);
			} catch (\Exception $e) {
				$logData["reason"] = "exception at QuestionController listAction QuestionTable CountAllValid";
				$logData["message"] = $e->getMessage();
				$log = $LogModule->SaveLog($logData);
				print_r($log);
				exit;
			}

			if (empty($sqlWhere)) {
				try { 
					$questionDatas = $questionTb->GetValidList($userCode);
				} catch (\Exception $e) {
					$logData["reason"] = "exception at QuestionController listAction QuestionTable GetValidList";
					$logData["message"] = $e->getMessage();
					$log = $LogModule->SaveLog($logData);
					print_r($log);
					exit;
				}
			} else {
				try { 
					$questionDatas = $questionTb->GetValidListBySearch($userCode, $sqlWhere);
				} catch (\Exception $e) {
					$logData["reason"] = "exception at QuestionController listAction QuestionTable GetValidListBySearch";
					$logData["message"] = $e->getMessage();
					$log = $LogModule->SaveLog($logData);
					print_r($log);
					exit;
				}
			}
		}
		/* ここまで */

		$questionDatas->setCurrentPageNumber($page);
		$questionDatas->setItemCountPerPage(10);
		$datas["questionDatas"] = $questionDatas;
		return $this->SetViewModel($datas, "/question/question_list.phtml");
	}
	
	/** When you click 新規登録 button on 一覧 page */
	public function inputAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "問項登録"];
		$datas["title"] = "問項登録";
		$datas["optionDatas"] = $this->GetOptionDatasForInput();
		$datas["languageCodeDatas"] = ["ko" => "韓国語"];

		$adminTb = $this->getServiceLocator()->get("AdminTable");
		try { $datas["adminDatas"] = $adminTb->ReadAllList(); }
		catch (\Exception $e) { print_r($e->getMessage()); exit; }

		return $this->SetViewModel($datas, "/question/question_input.phtml");
	}

	/** When you click 登録 button on 問題登録 page */
	public function confirmAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "問項登録", "登録確認"];
		$datas["title"] = "登録確認";
		$datas["optionDatas"] = $this->GetOptionDatas();
		$datas["languageCodeDatas"] = ["ko" => "韓国語"];

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
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "問項詳細"];
		$datas["optionDatas"] = $this->GetOptionDatas();
	
		$index = $this->params()->fromRoute("index");

		$questionTable = $this->getServiceLocator()->get("QuestionTable");
		$questionData = array();
		try { $questionData = $questionTable->ReadByIdx($index); }
		catch (\Exception $e) { print_r($e->getMessage()); exit; }
		$datas["questionData"] = $questionData;
		$datas["languageCodeDatas"] = ["ko" => "韓国語"];

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
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "問項詳細", "問項修正"];
		$datas["title"] = "問項修正";
		$datas["optionDatas"] = $this->GetOptionDatasForInput();
		$datas["languageCodeDatas"] = ["ko" => "韓国語"];

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
		$questionData = $post;

		foreach ($questionData as $key => $value) {
			if ($value == null) {
				$questionData[$key] = "";
				continue;
			}
			$questionData[$key] = str_replace("\n", "{{n}}", $value);
		}

		$LogModule = new LogModule();
		$exceptionLogReason = "exception at QuestionController registAction ";

		$optionTb = $this->getServiceLocator()->get("OptionTable");
		try {
			$questionData["status"] = $optionTb->ReadByText("新規")["idx"];
		} catch (\Exception $e) {
			$logData["reason"] = $exceptionLogReason . "OptionTable ReadByText";
			$logData["message"] = $e->getMessage();
			$log = $LogModule->SaveLog($logData);
			die($log);
		}

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		try {
			$questionTb->CreateQuestion($questionData);
		} catch (\Exception $e) {
			$logData["reason"] = $exceptionLogReason . "QuestionTable CreateQuestion";
			$logData["message"] = $e->getMessage();
			$log = $LogModule->SaveLog($logData);
			die($log);
		}

		die("success");
	}

	public function updateAction() {
		$post = $this->params()->fromPost();
		$questionData = $post;
		$sqlWhere["idx"] = $questionData["idx"];
		unset($questionData["idx"]);

		foreach ($questionData as $key => $value) {
			if ($value == null) {
				$questionData[$key] = "";
				continue;
			}

			$questionData[$key] = str_replace("\n", "{{n}}", $value);
		}

		$questionData["admin_approve"] = null;
		$questionData["date_approve"] = null;

		$LogModule = new LogModule();
		$exceptionLogReason = "exception at QuestionController updateAction ";

		$optionTb = $this->getServiceLocator()->get("OptionTable");
		try {
			$questionData["status"] = $optionTb->ReadByText("承認依頼")["idx"];
		} catch (\Exception $e) {
			$logData["reason"] = $exceptionLogReason . "OptionTable ReadByText";
			$logData["message"] = $e->getMessage();
			$log = $LogModule->SaveLog($logData);
			die($log);
		}

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		try {
			$questionTb->UpdateByIdx($sqlWhere, $questionData);
		} catch (\Exception $e) {
			$logData["reason"] = $exceptionLogReason . "QuestionTable UpdateByIdx";
			$logData["message"] = $e->getMessage();
			$log = $LogModule->SaveLog($logData);
			die($log);
		}

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

		$approvedIdxDatas = array();
		$beforeNotes = array();
		foreach ($idxDatas as $idx) {
			try { $result = $questionTb->ReadByIdx($idx); }
			catch (\Exception $e) { die($e->getMessage()); }
			if ($result["date_approve"] != null) { $approvedIdxDatas[] = $result["idx"]; }
			else { array_push($beforeNotes, $result["note"]); }
		}

		if ($approvedIdxDatas) {
			$error = ["reason" => "approved", "data_discrip" => "array of approved data's idx", "data" => $approvedIdxDatas];
			die(json_encode($error));
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
		$log = new LogModule();

		if ($_FILES["file"]["error"] == "0") {
			header("Content-Type: text/html; charset=utf-8");
			/*
				作成：朴昰成
				修正：朴昰成
				修正日：24/06/19
			*/

			/* 修正前：
			if ($_FILES["file"]["type"] != "text/csv") { die($log->SaveLog(["reason" => "not csv file"])); }
			*/

			/* 修正後： */
			if ($_FILES["file"]["type"] != "text/csv") {
				$logData["reason"] = "not csv file";
				$log->SaveLog(["reason" => "not csv file"]);
				die(json_encode($logData));
			}
			/* ここまで */

			$csvStrings = array();
			try {
			$csvStrings = array_map("str_getcsv", file($_FILES["file"]["tmp_name"]));
			} catch (\Exception $e) {
				/*
					作成：朴昰成
					修正：朴昰成
					修正日：24/06/19
				*/

				/* 修正前：
				$logData["reason"] = "not csv file";
				$logData["message"] = $e->getMessage();
				die($log->SaveLog($logData));
				*/

				/* 修正後： */
				$logData["reason"] = "fail to open file";
				$logData["message"] = $e->getMessage();
				$log->SaveLog($logData);
				die ($logData);
				/* ここまで */
			}
			$keys = $csvStrings[0];
			unset($csvStrings[0]);
			// exception handling
			if (ord($keys[0][0]) == 239 && ord($keys[0][1]) == 187 && ord($keys[0][2]) == 191) {
				$keys[0] = substr($keys[0], 3);
			}

			$questionTb = $this->getServiceLocator()->get("QuestionTable");
			$optionTb = $this->getServiceLocator()->get("OptionTable");

			$questionData = array();
			$logDatas = array();
			foreach ($csvStrings as $index => $csvDatas) {
				foreach ($csvDatas as $idx => $data) {
					$data = str_replace("{{44}}", ",", $data);
					$questionData[$keys[$idx]] = $data;
				}

				for ($i = 4; $i <= 5; $i++) {
					if (!isset($questionData["answer" . $i]) || trim($questionData["answer" . $i]) == "") {
						$questionData["answer" . $i] = null;
					}
				}

				switch($questionData["level"]) {
					case "無級":
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
					/*
						作成：朴昰成
						作成日：24/06/19
					*/
					case "上級":
						$questionData["level"] = 3;
						break;
					/* ここまで */
				}

				$session = new Container("user");
				/*
					作成：朴昰成
					修正：朴昰成
					修正日：24/06/19
				*/

				/* 修正前：
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
					$logData["reason"] = "fail insert";
					$logData["message"] = $e->getMessage();
					$logDatas[$index + 1] = $logData;
				}
				*/

				/* 修正後： */
				try {
					$questionData["class1st"] = $optionTb->ReadByText($questionData["class1st"])["idx"];
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
					$logData["reason"] = "exception at QuestionController createByCsvAction make question data";
					$logData["message"] = $e;
					$logDatas[$index] = $logData;
				}
				/* ここまで */
			}
			if ($logDatas) {
				$noDatas = array();
				foreach ($logDatas as $index => $logData) {
					$log->SaveLog($logData);
					$noDatas[] = $index;
				}
				die(json_encode($noDatas));
			}

			die("success");
		}

		/*
			作成：朴昰成
			修正：朴昰成
			修正日：24/06/19
		*/

		/* 修正前：
		die($log->SaveLog(["reason" => "fail to open file"]));
		*/

		/* 修正後： */
		$logData["reason"] = "fail to open file";
		$log->SaveLog($logData);
		die(json_encode($logData));
		/* ここまで */
	}

	/** Read Option datas Organize by Type (class2nd's idx is text)
	 * @return array $optionDatas ["type" => "text"]
	 */
	public function ReadOptionOrganizeByType() {
		$beforeOptionDatas = array();
		$optionTb = $this->getServiceLocator()->get("OptionTable");
		try { $beforeOptionDatas = $optionTb->ReadValid(); }
		catch (\Exception $e) { print_r($e->getMessage()); exit; }

		$optionDatas = array();
		$class2ndDatas = array();
		$other = array();
		foreach ($beforeOptionDatas as $data) {
			if ($data["text"] == "その他") {
				$other = $data;
				continue;
			}
			if ($data["type"] == "class2nd") {
				$class2ndDatas[] = $data;
				continue;
			}

			if (!isset($optionDatas[$data["type"]])) {
				$optionDatas[$data["type"]] = array();
			}
			$optionDatas[$data["type"]][] = $data;
		}

		$optionDatas["class1st"][] = $other;

		$optionDatas["class2nd"] = array();
		foreach ($class2ndDatas as $data) {
			if (!isset($optionDatas["class2nd"][$data["text"]])) {
				$data["idx"] = $data["text"];
				$optionDatas["class2nd"][$data["text"]] = $data;
			}
		}

		return $optionDatas;
	}
}