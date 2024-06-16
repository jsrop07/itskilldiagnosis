<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Admin\Model\LogModule;

class DiagnosisController extends AbstractActionController {
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
		header("Location: ./diagnosis/list");
		exit;
	}

	public function listAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断問題管理"];
		$datas["optionDatas"] = $this->GetOptionDatas();
		// array_merge occurs error
		$optionDatas = $this->GetOptionDatasOrganizeByType();
		foreach ($optionDatas as $type => $data) {
			$datas["optionDatas"][$type] = $data;
		}

		$optionTb = $this->getServiceLocator()->get("OptionTable");
		$beforeClass2ndDatas = array();
		try { $beforeClass2ndDatas = $optionTb->ReadByOption(["type" => "class2nd"]); }
		catch (\Exception $e) { print_r($e->getMessage()); exit; }
		
		$class2ndDatas = array();
		foreach ($beforeClass2ndDatas as $data) {
			if (!in_array($data["text"], $class2ndDatas)) {
				if ($data["text"] == "C++") { $data["text"] = "Cpp"; }
				array_push($class2ndDatas, $data["text"]);
			}
		}
		$datas["class2ndDatas"] = $class2ndDatas;

		$query = $this->params()->fromQuery();

		// Get Current Page
		$page = 1;
		if (isset($query["page"])) {
			$page = $query["page"];
			unset($query["page"]);
		}

		$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");
		$datas["totalNum"] = $diagnosisTb->CountAllList();

		$diagnosisDatas = array();
		$sqlWhere = array();
			
		if (isset($query["select"])) {
			$selectData = explode("-", $query["select"]);

			if (isset($selectData[1])) {
				if ($selectData[0] == "class") {
					if (isset($selectData[2])) { $sqlWhere["class2nd"] = $selectData[2]; }
					else { $sqlWhere["class1st"] = $selectData[1]; }
				}
				else { $sqlWhere[$selectData[0]] = $selectData[1]; }
			}
		}

		if ($sqlWhere) {
			try { $diagnosisDatas = $diagnosisTb->GetListBySearch($sqlWhere); }
			catch (\Exception $e) { print_r($e->getMessage()); exit; }
		}
		else {
			try {$diagnosisDatas = $diagnosisTb->GetAllList(); }
			catch (\Exception $e) { print_r($e->getMessage()); exit; }
		}
		
		$diagnosisDatas->setCurrentPageNumber($page);
		$diagnosisDatas->setItemCountPerPage(10);
		$datas["diagnosisDatas"] = $diagnosisDatas;
		return $this->SetViewModel($datas, "/diagnosis/diagnosis_list.phtml");
	}
	
	/** When you click 新規登録 button on 一覧 page */
	public function inputAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断問題管理", "問題登録"];
		$datas["title"] = "問題登録";
		$datas["optionDatas"] = $this->GetAllOption();
		$datas["resultDatas"] = $this->GetResultDatas();

		// Check return from 登録確認　page
		$post = $this->params()->fromPost();
		if (isset($post["idx"])) {
			$datas["diagnosisData"] = $post;
		}

		return $this->SetViewModel($datas, "/diagnosis/diagnosis_input.phtml");
	}

	/** When you click 登録 button on 診断問題登録 page */
	public function confirmAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断問題管理", "問題登録" ,"登録確認"];
		$datas["title"] = "登録確認";
		$datas["optionDatas"] = $this->GetOptionDatas();

		$post = $this->params()->fromPost();

		$LogModule = new LogModule();
		$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");
		$diagnosisData = $this->LeaveDiagnosisTableData($post);

		$code = "";
		do {
			$code = str_pad($post["class1st"], 2, "0", STR_PAD_LEFT);
			$code .=  "-" . str_pad($post["class2nd"], 2, "0", STR_PAD_LEFT);
			$code .= "-" . $post["level"] . chr(rand(65, 90));

			try {
				$result = $diagnosisTb->ReadByCode($code);
			} catch (\Exception $e) {
				$logData["reason"] = "exception at DiagnosisController confirmAction DiagnosisTable ReadByCode";
				$logData["message"] = $e->getMessage();
				print_r($LogModule->SaveLog($logData));
				exit;
			}
		} while (!empty($result));
		$diagnosisData["code"] = $code;

		$datas["diagnosisData"] = $diagnosisData;

		$datas["questionDatas"] = $this->ReadQuestionDatasByIdxs($post["question_idxs"]);

		return $this->SetViewModel($datas, "/diagnosis/diagnosis_confirm.phtml");
	}

	/** When you choose list data on 問題一覧 page */
	public function detailAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断問題管理", "問題詳細"];
		$datas["optionDatas"] = $this->GetOptionDatas();

		// Get Code
		$idx = $this->params()->fromRoute("index");

		$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");
		try { $datas["diagnosisData"] = $diagnosisTb->ReadByIdx($idx); }
		catch (\Exception $e) { print_r($e->getMessage()); exit; }

		$questionTb = $this->getServiceLocator()->get("QuestionTable");

		$questionIdxs = explode(",", $datas["diagnosisData"]["question_idxs"]);
		$questionDatas = array();
		foreach ($questionIdxs as $index => $idx) {
			try { $questionDatas[$index] = $questionTb->ReadByIdx($idx); }
			catch (\Exception $e) { print_r($e->getMessage()); exit; }
		}
		$datas["questionDatas"] = $questionDatas;

		return $this->SetViewModel($datas, "/diagnosis/diagnosis_detail.phtml");
	}

	/** When you click 修正 button on 診断問題詳細 page */
	public function editAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断問題管理", "問題詳細", "問題修正"];
		$datas["title"] = "問題修正";
		$datas["optionDatas"] = $this->GetOptionDatasForInput();
		$datas["resultDatas"] = $this->GetResultDatas();

		$idx = $this->params()->fromRoute("index");	// Get Code from url

		$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");
		try { $datas["diagnosisData"] = $diagnosisTb->ReadByIdx($idx); }
		catch (\Exception $e) { print_r($e->getMessage()); exit; }
		
		return $this->SetViewModel($datas, "/diagnosis/diagnosis_input.phtml");
	}

	function registAction() {
		$post = $this->params()->fromPost();

		$sqlValue = $this->LeaveDiagnosisTableData($post);
		
		$session = new Container("user");
		$sqlValue["admin_create"] = $session["code"];
		$sqlValue["date_start"] = date("Y-m-d H:i:s");

		$LogModule = new LogModule();
		$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");

			try {
				$diagnosisTb->CreateDiagnosis($sqlValue);
			} catch (\Exception $e) {
				$logData["reason"] = "exception at DiagnosisController registAction DiagnosisTable CreateDiagnosis";
				$logData["message"] = $e->getMessage();
				$logMessage = $LogModule->SaveLog($logData);
				die($logMessage);
			}

		die ("success");
	}

	public function updateAction() {
		$post = $this->params()->fromPost();
		$LogModule = new LogModule();
		$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");

		try {
			$diagnosisTb->RemoveDiagnosis($post["idx"]);
		} catch (\Exception $e) {
			$logData["reason"] = "exception at DiagnosisController updateAction DiagnosisTable RemoveDiagnosis";
			$logData["message"] = $e->getMessage();
			$log = $LogModule->SaveLog($logData);
			die($log);
		}

		$sqlValue = $this->LeaveDiagnosisTableData($post);
		unset($sqlValue["idx"]);
		$sqlValue["date_start"] = date("Y-m-d H:i:s");
		try {
			$diagnosisTb->CreateDiagnosis($sqlValue);
		} catch (\Exception $e) {
			$logData["reason"] = "exception at DiagnosisController updateAction DiagnosisTable CreateDiagnosis";
			$logData["message"] = $e->getMessage();
			$log = $LogModule->SaveLog($logData);
			die($log);
		}

		die ("success");
	}

	public function removeAction() {
		$idxs = $this->params()->fromPost("idxs");
		$idxDatas = explode(",", $idxs);

		$LogModule = new LogModule();
		$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");
		foreach ($idxDatas as $idx) {
			try {
				$diagnosisTb->RemoveDiagnosis($idx);
			} catch (\Exception $e) {
				$logData["reason"] = "exception at DiagnosisController removeAction DiagnosisTable RemoveDiagnosis";
				$logData["message"] = $e->getMessage();
				$log = $LogModule->SaveLog($logData);
				die($log);
			}
		}
		die("success");
	}

	public function readAction() {
		$route =$this->params()->fromRoute("index");
		$post = $this->params()->fromPost();

		switch ($route) {
			case "question":
				$sqlWhere = $post;

				$questionTb = $this->getServiceLocator()->get("QuestionTable");
				try { $questionDatas = $questionTb->ReadForDiagnosis($sqlWhere); }
				catch (\Exception $e) { die($e->getMessage()); }

				$optionTb = $this->getServiceLocator()->get("OptionTable");
				foreach($questionDatas as $index => $data) {
					$question["idx"] = $data["idx"];
					$question["title"] = $data["title"];
					$question["level"] = $data["level"];
					$question["question"] = $data["question"];

					for ($i = 1; $i <= 5; $i++) {
						$question["answer" . $i] = $data["answer" . $i];
					}

					try {
						$question["class1st"] = $optionTb->ReadByIdx($data["class1st"])["text"];
						$question["class2nd"] = $optionTb->ReadByIdx($data["class2nd"])["text"];
					} catch (\Exception $e) {
						die($e->getMessage());
					}
					$question["point"] = $data["point"];

					$question["question"] = $data["question"];
					for ($i = 1; $i <= 5; $i++) {
						$question["answer" . $i] = $data["answer" . $i];
					}

					$questionDatas[$index] = $question;
				}

				die(json_encode($questionDatas));
				break;
			case "list":
				$questionDatas = $this->ReadQuestionDatasByIdxs($post["question_idxs"]);
				$questionDatas = $this->ChangeQuestionDatasForDiagnosisList($questionDatas);
				if (gettype($questionDatas) == "string") { die($questionDatas); }
				die (json_encode($questionDatas));
				break;
		}
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

	/** leave data of diagnosis table field
	 * @param array $datas
	 * @return array $diagnosisData
	*/
	function LeaveDiagnosisTableData($datas) {
		$diagnosisData = $datas;

		foreach ($datas as $field => $value) {
			if ($field == "idx") { continue; }
			if ($field == "code") { continue; }
			if ($field == "title") { continue; }
			if ($field == "class1st") { continue; }
			if ($field == "class2nd") { continue; }
			if ($field == "level") { continue; }
			if ($field == "question_num") { continue; }
			if ($field == "question_idxs") { continue; }
			if ($field == "time_limit") { continue; }
			if ($field == "result_points") { continue; }
			if ($field == "result_texts") { continue; }
			if ($field == "result_comments") { continue; }
			if ($field == "admin_create") { continue; }
			if ($field == "date_start") { continue; }
			if ($field == "date_end") { continue; }
			if ($field == "point_total") { continue; }
			unset($diagnosisData[$field]);
		}

		return $diagnosisData;
	}

	/** change data for diagnosis list
	 * @param array $beforeQuestionDatas
	 * @return array $questionDatas ["idx", "title", "class1st", "class2nd", "level", "point"]
	 */
	function ChangeQuestionDatasForDiagnosisList($beforeQuestionDatas) {
		$LogModule = new LogModule();
		$optionTb = $this->getServiceLocator()->get("OptionTable");

		$questionDatas = array();
		foreach($beforeQuestionDatas as $data) {
			$questionData["idx"] = $data["idx"];
			$questionData["title"] = $data["title"];
			$questionData["level"] = $data["level"];
			$questionData["question"] = $data["question"];
			$questionData["point"] = $data["point"];

			for ($i = 1; $i <= 5; $i++) {
				$questionData["answer" . $i] = $data["answer" . $i];
			}

			try {
				$questionData["class1st"] = $optionTb->ReadByIdx($data["class1st"])["text"];
				$questionData["class2nd"] = $optionTb->ReadByIdx($data["class2nd"])["text"];
			} catch (\Exception $e) {
				$logData["reason"] = "exception at DiagnosisController ChangeQuestionDatasForDiagnosisList Optiontable ReadByIdx";
				$logData["message"] = $e->getMessage();
				return $LogModule->SaveLog($logData);
			}

			$questionDatas[] = $questionData;
		}

		return $questionDatas;
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

	/** Read Option datas Organize by Type
	 * @return array $optionDatas ["type" => data] (class2nd = ["class2nd" => ["class_upper" => data]])
	 */
	public function GetOptionDatasOrganizeByType() {
		$optionTb = $this->getServiceLocator()->get("OptionTable");

		$beforeOptionDatas = array();
		try { $beforeOptionDatas = $optionTb->ReadValid(); }
		catch (\Exception $e) { print_r($e->getMessage()); exit; }

		$optionDatas = array();
		$class2ndDatas = array();
		$other = array();
		foreach ($beforeOptionDatas as $data) {
			if ($data["type"] == "level") { continue; }
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

		foreach ($class2ndDatas as $data) {
			if (!isset($optionDatas["class2nd"][$data["class_upper"]])) {
				$optionDatas["class2nd"][$data["class_upper"]] = array();
			}
			$optionDatas["class2nd"][$data["class_upper"]][] = $data;
		}

		return $optionDatas;
	}

	function GetResultDatas() {
		$resultDatas["text"] = array();
		$resultDatas["text"][] = "優秀";
		$resultDatas["text"][] = "やや優秀";
		$resultDatas["text"][] = "努力が必要";
		$resultDatas["text"][] = "IT職業に向いてない";
		
		$resultDatas["comment"] = array();
		$resultDatas["comment"][] = "素晴らしい結果です。IT の概念に対するあなたの知識と理解は並外れたものです。上位 10% に入るスコアは、あなたが内容をしっかりと理解していることを示す重要な成果です。より高い能力（スキル）を持つように挑戦し続けてください。";
		$resultDatas["comment"][] = "よくやりました！ IT の概念をしっかりと理解しており、内容を習得する段階に順調に進んでいることを示しています。引き続き今まで通り頑張って頂き、将来的にはさらに高い成果を目指してください。";
		$resultDatas["comment"][] = "よく頑張りましたね。あなたは主要な IT 概念をある程度理解していると思いますが、改善の余地があるので、知識とスキルをさらに高めるために学習を続けてください。";
		$resultDatas["comment"][] = "ご尽力いただき、ありがとうございます。現在の状況だと、さらなる見直しと改善が必要だと考えられます。時間をかけて自分の学習方法を再検討し、必要に応じて遠慮せずに助けを求めてください。";

		return $resultDatas;
	}

	/** Make QuestionDatas by Point (Change to read Directly)
	 * @param array $whereDatas array[class1st, class2nd, level]
	 * @return mixed $questionDatas
	*/
	function ReadQuestionDatasForDiagnosis($sqlWhere) {
		$questionTb = $this->getServiceLocator()->get("QuestionTable");

		$questionDatas = array();
		for ($i = 1; $i <= 5; $i++) {
			$sqlWhere["point"] = $i;
			$questionDatas[$i] = $questionTb->ReadForDiagnosis($sqlWhere);
		}

		return $questionDatas;
	}

	function PointQuestionsToJson($pointQuestionDatas) {
		$questionDatas = array();

		for ($i = 1; $i <= 5; $i++) {
			if (!isset($pointQuestionDatas[$i])) { continue; }
			foreach ($pointQuestionDatas[$i] as $data) {
				array_push($questionDatas, $data);
			}
		}

		$optionTb = $this->getServiceLocator()->get("OptionTable");
		foreach ($questionDatas as $index => $data) {
			$questionDatas[$index]["type"] = $optionTb->ReadByIdx($data["type"])["text"];
		}

		return json_encode($questionDatas);
	}

	function ReadQuestionDatasByIdxs($questionIdxs) {
		$questionIdxs = explode(",", $questionIdxs);
		
		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		$optionTb = $this->getServiceLocator()->get("OptionTable");
		$questionDatas = array();
		foreach ($questionIdxs as $idx) {
			try {
				$questionData = $questionTb->ReadByIdx($idx);
				$questionData["type"] = $optionTb->ReadByIdx($questionData["type"])["text"];
				array_push($questionDatas,  $questionData);
			} catch (\Exception $e) {
				die($e->getMessage());
				exit;
			}
		}

		return $questionDatas;
	}

	function ReadQuestionIdxsByScore($sqlWhere) {
		$questionTb = $this->getServiceLocator()->get("QuestionTable");

		$questionDatasByScore = array();
		for ($i = 1; $i <= 5; $i++) {
			$sqlWhere["point"] = $i;
			try { $questionDatas = $questionTb->ReadForDiagnosis($sqlWhere); }
			catch (\Exception $e) { die($e->getMessage()); }

			$questionDatasByScore[$i] = array();
			foreach ($questionDatas as $questionData) {
				$data["idx"] = $questionData["idx"];
				$data["point"] = $questionData["point"];

				array_push($questionDatasByScore[$i], $data);
			}
		}

		return $questionDatasByScore;
	}

	function CreateQuestionList($beforeQuestionIdxsByScore, $question_num) {
		$beforeQuestionIdxDatas = array();
		foreach ($beforeQuestionIdxsByScore as $datas) {
			$idxDatas = array();
			foreach ($datas as $data) {
				$idxDatas[] = $data["idx"];
			}
			$beforeQuestionIdxDatas = array_merge($beforeQuestionIdxDatas, $idxDatas);
		}

		if ($question_num > count($beforeQuestionIdxDatas)) {
			return $beforeQuestionIdxDatas;
		}

		$indexDatas = array_rand($beforeQuestionIdxDatas, $question_num);
		$questionIdxDatas = array();
		foreach ($indexDatas as $index) {
			$questionIdxDatas[] = $beforeQuestionIdxDatas[$index];
		}
		$questionDatas = $questionIdxDatas;

		return $questionDatas;
	}

	function QuestionIdxsByScoreToQuestionIdxs($questionIdxsByScore) {
		$questionIdxs = array();

		foreach ($questionIdxsByScore as $beforeQuestionIdxs) {
			foreach ($beforeQuestionIdxs as $data) {
				array_push($questionIdxs, $data["idx"]);
			}
		}

		return $questionIdxs;
	}

	/** make optiondatas organize by idx, type
	 * @return array $optionDatas ["idx" => $data], ["type" => $data]
	 */
	function GetAllOption() {
		$optionDatas = $this->GetOptionDatas();
		// array_merge occurs error
		$optionDatasoOrgType = $this->GetOptionDatasOrganizeByType();
		foreach ($optionDatasoOrgType as $type => $data) {
			$optionDatas[$type] = $data;
		}

		return $optionDatas;
	}
}