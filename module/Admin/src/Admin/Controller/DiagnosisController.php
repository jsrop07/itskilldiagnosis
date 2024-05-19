<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

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
		// header("Location: ./diagnosis/list");
		// exit;
		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		$sqlWhere["level"] = 1;
		$sqlWhere["class1st"] = 7;
		$sqlWhere["class2nd"] = 13;
		try { $datas["questionDatas"] = $questionTb->ReadForDiagnosis($sqlWhere); }
		catch (\Exception $e) { print_r($e->getMessage()); exit; }
		return $this->SetViewModel($datas, "/diagnosis/test.phtml");
	}

	public function listAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断書管理"];
		$datas["optionDatas"] = $this->GetOptionDatas();
		$datas["inputOptionDatas"] = $this->GetOptionDatasForInput();

		$optionTb = $this->getServiceLocator()->get("OptionTable");
		$beforeClass2ndDatas = array();
		try { $beforeClass2ndDatas = $optionTb->ReadByOption(["type" => "class2nd"]); }
		catch (\Exception $e) { print_r($e->getMessage()); exit; }
		
		$class2ndDatas = array();
		foreach ($beforeClass2ndDatas as $data) {
			if (!in_array($data["text"], $class2ndDatas)) {
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

		$diagnosisDatas = array();
		if (!empty($query)) {
			$datas["searchData"] = $query;
			
			$searchKey = array_keys($query)[0];
			if ($searchKey == "class2nd") {
				$optionTb = $this->getServiceLocator()->get("OptionTable");
				$sqlWheres["text"] = $query["class2nd"];
				try { $class2ndDatas = $optionTb->ReadByOption($sqlWheres); }
				catch (\Exception $e) { print_r($e->getMessage()); exit; }

				$sqlWhere["class2nd"] = array();
				foreach ($class2ndDatas as $data) {
					$sqlWhere["class2nd"][] = $data["idx"];
				}
				$query = $sqlWhere;
			}
			try { $diagnosisDatas = $diagnosisTb->GetListByOption($query); }
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
		$datas["breadcrumbData"] = ["ITスキル診断書管理", "診断書登録"];
		$datas["title"] = "診断書登録";
		$datas["optionDatas"] = $this->GetOptionDatasForInput();
		$datas["resultDatas"] = $this->GetResultDatas();

		// Check return from 登録確認　page
		$post = $this->params()->fromPost();
		if (isset($post["idx"])) {
			$datas["diagnosisData"] = $post;
		} 
		/* Delete 24/05/17
		削除前： else {
			$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");

			// Make Code
			$code = "";
			$result = array();
			do {
				$code = chr(rand(65, 90)) . "-" . date("ymd") . str_pad(rand(0, 99), 2, "0", STR_PAD_LEFT);
				try { $result = $diagnosisTb->ReadByCode($code);}
				catch (\Exception $e) { print_r($e->getMessage()); exit; }
			} while (!empty($result));

			$datas["code"] = $code;
		}
		*/

		return $this->SetViewModel($datas, "/diagnosis/diagnosis_input.phtml");
	}

	/** When you click 登録 button on 診断書登録 page */
	public function confirmAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "診断書登録" ,"登録確認"];
		$datas["title"] = "診断書確認";
		$datas["optionDatas"] = $this->GetOptionDatas();

		$post = $this->params()->fromPost();

		$diagnosisDatas = $this->getServiceLocator()->get("DiagnosisTable-Admin");
		$code = "";
		do {
			$code = str_pad($post["class1st"], 2, "0", STR_PAD_LEFT);
			$code .=  "-" . str_pad($post["class2nd"], 2, "0", STR_PAD_LEFT);
			$code .= "-" . $post["level"] . chr(rand(65, 90));

			try { $result = $diagnosisDatas->ReadByCode($code); }
			catch (\Exception $e) { print_r($e->getMessage()); exit; }
		} while (!empty($result));

		$datas["diagnosisData"] = $post;
		$datas["diagnosisData"]["code"] = $code;

		$datas["questionDatas"] = $this->ReadQuestionDatasByIdxs($post["question_idxs"]);

		return $this->SetViewModel($datas, "/diagnosis/diagnosis_confirm.phtml");
	}

	/** When you choose list data on 問題一覧 page */
	public function detailAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断書管理", "診断書詳細"];
		$datas["title"] = "診断書詳細";
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

	/** When you click 修正 button on 診断書詳細 page */
	public function editAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断書管理", "診断書詳細", "診断書修正"];
		$datas["title"] = "診断書修正";
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

		$sqlValue["code"] = $post["code"];
		$sqlValue["class1st"] = $post["class1st"];
		$sqlValue["class2nd"] = $post["class2nd"];
		$sqlValue["level"] = $post["level"];
		$sqlValue["title"] = $post["title"];
		$sqlValue["question_num"] = $post["question_num"];
		$sqlValue["time_limit"] = $post["time_limit"];
		$sqlValue["question_idxs"] = $post["question_idxs"];
		
		$sqlValue["result_points"] = array();
		$sqlValue["result_texts"] = array();
		$sqlValue["result_comments"] = array();
		for ($i = 1; $i <= 4; $i++) {
			array_push($sqlValue["result_points"], $post["point" . $i]);
			array_push($sqlValue["result_texts"], $post["text" . $i]);
			array_push($sqlValue["result_comments"], $post["comment" . $i]);
		}
		$sqlValue["result_points"] = implode(",", $sqlValue["result_points"]);
		$sqlValue["result_texts"] = implode(",", $sqlValue["result_texts"]);
		$sqlValue["result_comments"] = implode(",", $sqlValue["result_comments"]);
		
		$session = new Container("user");
		$sqlValue["admin_create"] = $session["code"];
		$sqlValue["date_start"] = date("Y-m-d H:i:s");

		$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");
		try { $diagnosisTb->createDiagnosis($sqlValue); }
		catch (\Exception $e) { die($e->getMessage()); }

		die ("success");
	}

	public function updateAction() {
		$post = $this->params()->fromPost();
		$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");

		try { $diagnosisTb->RemoveDiagnosis($post["idx"]); }
		catch (\Exception $e) { die($e->getMessage()); }

		$sqlValue["code"] = $post["code"];
		$sqlValue["title"] = $post["title"];
		$sqlValue["class1st"] = $post["class1st"];
		$sqlValue["class2nd"] = $post["class2nd"];
		$sqlValue["level"] = $post["level"];
		$sqlValue["question_num"] = $post["question_num"];
		$sqlValue["question_idxs"] = $post["question_idxs"];
		$sqlValue["time_limit"] = $post["time_limit"];
		$pointDatas = array();
		$textDatas = array();
		$commentDatas = array();
		for ($i = 1; $i <= 4; $i++) {
			$pointDatas[] = $post["point" . $i];
			$textDatas[] = $post["text" . $i];
			$commentDatas[] = $post["comment" . $i];
		}
		$sqlValue["result_points"] = implode(",", $pointDatas);
		$sqlValue["result_texts"] = implode(",", $textDatas);
		$sqlValue["result_comments"] = implode(",", $commentDatas);
		$sqlValue["date_start"] = date("Y-m-d H:i:s");
		try { $diagnosisTb->CreateDiagnosis($sqlValue); }
		catch (\Exception $e) { die($e->getMessage()); }

		die ("success");
	}

	public function removeAction() {
		$idxs = $this->params()->fromPost("idxs");
		$idxArr = explode(",", $idxs);

		$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");
		foreach ($idxArr as $idx) {	
			try { $diagnosisTb->RemoveDiagnosis($idx); }
			catch (\Exception $e) { die($e->getMessage()); }
		}
		die("success");
	}

	public function readAction() {
		$route =$this->params()->fromRoute("index");
		$post = $this->params()->fromPost();

		if (isset($post["question_idxs"])) {
			die (json_encode($this->ReadQuestionDatasByIdxs($post["question_idxs"])));
		}
		
		/* test */
		$sqlWhere["class1st"] = $post["class1st"];
		$sqlWhere["class2nd"] = $post["class2nd"];
		$sqlWhere["level"] = $post["level"];
		/* test */
		switch ($route) {
			case "question":

				$questionTb = $this->getServiceLocator()->get("QuestionTable");
				try { $questionDatas = $questionTb->ReadForDiagnosis($sqlWhere); }
				catch (\Exception $e) { die($e->getMessage()); }

				$optionTb = $this->getServiceLocator()->get("OptionTable");
				foreach($questionDatas as $index => $data) {
					$question["idx"] = $data["idx"];
					$question["title"] = $data["title"];
					try { $question["type"] = $optionTb->ReadByIdx($data["type"])["text"]; }
					catch (\Exception $e) { die($e->getMessage()); }
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
				// /* test */
				// $post["question_num"] = 20;
				// /* test */
				$questionTb = $this->getServiceLocator()->get("QuestionTable");
				$questionIdxsByScore = $this->ReadQuestionIdxsByScore($sqlWhere);

				$questionIdxsByScore = $this->CreateQuestionList($questionIdxsByScore, $post["question_num"]);
				$questionIdxs = $this->QuestionIdxsByScoreToQuestionIdxs($questionIdxsByScore);
				die(json_encode($questionIdxs));
				break;
		}

		$sqlWhere = $post;

		unset($sqlWhere["question_num"]);
		unset($sqlWhere["time_limit"]);
		$totalQuestionDatas = $this->ReadQuestionDatasForDiagnosis($sqlWhere);

		$totalPoint = 0;
		for ($i = 1; $i <= 5; $i++) {
			$totalPoint += count($totalQuestionDatas[$i]) * $i;
		}

		if ($totalPoint <= 100) {
			die($this->PointQuestionsToJson($totalQuestionDatas));
		}

		$questionDatas = array();
		for ($i = 1; $i <= 5; $i++) {
			$questionDatas[$i] = array();
		}

		$totalPoint = 0;
		for ($i = 0; $i < $post["question_num"]; $i++) {
			do { $point = rand(1, 5); }
			while (empty($totalQuestionDatas[$point]));
			$rndIdx = array_rand($totalQuestionDatas[$point]);
			$questionDatas[$point][$rndIdx] = $totalQuestionDatas[$point][$rndIdx];
			unset($totalQuestionDatas[$point][$rndIdx]);
			$totalPoint += $point;

			if ($totalPoint > 100) {
				$before = array();
				$after = array();
				$tempQuestionDatas = $totalQuestionDatas;
				while ($totalPoint > 100) {
					for ($j = 1; $j <= 4; $j++) {
						if (!empty($tempQuestionDatas[$j])) { break; }
						if ($j >= 5) { die($this->PointQuestionsToJson($questionDatas)); }
					}

					for ($j = 2; $j <= 5; $j++) {
						if (!empty($questionDatas[$j])) { break; }
						if ($j >= 5) { die($this->PointQuestionsToJson($questionDatas)); }
					}
					
					do { $point = rand(2, 5); }
					while (empty($questionDatas[$point]));
					$index = array_rand($questionDatas[$point]);
					$before["point"] = $point;
					$before["index"] = $index;
					$before["data"] = $questionDatas[$point][$index];
					unset($questionDatas[$point][$index]);

					do { $point = rand(1, $before["point"]); }
					while (empty($tempQuestionDatas[$point]));
					$index = array_rand($tempQuestionDatas[$point]);
					$after["point"] = $point;
					$after["index"] = $index;

					$questionDatas[$point][$index] = $tempQuestionDatas[$point][$index];
					unset($tempQuestionDatas[$point][$index]);

					$totalPoint += $after["point"] - $before["point"];
				}

				unset($totalQuestionDatas[$after["point"]][$after["index"]]);
				$totalQuestionDatas[$before["point"]][$before["index"]] = $before["data"];
			}

			if ($i + 1 == $post["question_num"] && $totalPoint != 100) {
				while ($totalPoint <= 95) {
					for ($j = 2; $j <= 5; $j++) {
						if (!empty($tempQuestionDatas[$j])) { break; }
						if ($j >= 5) { die($this->PointQuestionsToJson($questionDatas)); }
					}

					for ($j = 1; $j <= 4; $j++) {
						if (!empty($questionDatas[$j])) { break; }
						if ($j >= 5) { die($this->PointQuestionsToJson($questionDatas)); }
					}
					
					do { $point = rand(1, 4); }
					while (empty($questionDatas[$point]));
					$index = array_rand($questionDatas[$point]);
					$before["point"] = $point;
					$before["index"] = $index;
					$before["data"] = $questionDatas[$point][$index];
					unset($questionDatas[$point][$index]);

					do { $point = rand(2, $before["point"]); }
					while (empty($tempQuestionDatas[$point]));
					$index = array_rand($tempQuestionDatas[$point]);
					$after["point"] = $point;
					$after["index"] = $index;

					$questionDatas[$point][$index] = $tempQuestionDatas[$point][$index];
					unset($tempQuestionDatas[$point][$index]);

					$totalPoint += $after["point"] - $before["point"];
				}

				if ($totalPoint != 100) {
					$point = 100 - $totalPoint + 1;

					$index = array_rand($questionDatas[1]);
					unset($questionDatas[1][$index]);

					$index = array_rand($tempQuestionDatas[$point]);
					$questionDatas[$point][$index] = $tempQuestionDatas[$point][$index];
				}
			}
		}
		die($this->PointQuestionsToJson($questionDatas));
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

	function GetResultDatas() {
		$result["point1"] = 95;
		$result["point2"] = 90;
		$result["point3"] = 80;
		$result["point4"] = 0;
		$result["text1"] = "優秀";
		$result["text2"] = "やや優秀";
		$result["text3"] = "努力が必要";
		$result["text4"] = "IT職業に向いてない";
		$result["comment1"] = "優れている";
		$result["comment2"] = "適性に合うようである";
		$result["comment3"] = "成長の可能性が見える";
		$result["comment4"] = "適性が合わないようである";
		$resultDatas[0] = $result;

		$result["point1"] = 90;
		$result["point2"] = 80;
		$result["point3"] = 70;
		$result["point4"] = 0;
		$result["text1"] = "優秀";
		$result["text2"] = "やや優秀";
		$result["text3"] = "努力が必要";
		$result["text4"] = "IT職業に向いてない";
		$result["comment1"] = "優れている";
		$result["comment2"] = "適性に合うようである";
		$result["comment3"] = "成長の可能性が見える";
		$result["comment4"] = "適性が合わないようである";
		$resultDatas[1] = $result;

		$result["point1"] = 85;
		$result["point2"] = 75;
		$result["point3"] = 65;
		$result["point4"] = 0;
		$result["text1"] = "中級T1";
		$result["text2"] = "中級T2";
		$result["text3"] = "中級T3";
		$result["text4"] = "中級T4";
		$result["comment1"] = "中級C1";
		$result["comment2"] = "中級C2";
		$result["comment3"] = "中級C3";
		$result["comment4"] = "中級C4";
		$resultDatas[2] = $result;

		$result["point1"] = 80;
		$result["point2"] = 70;
		$result["point3"] = 60;
		$result["point4"] = 0;
		$result["text1"] = "優秀";
		$result["text2"] = "やや優秀";
		$result["text3"] = "努力が必要";
		$result["text4"] = "IT職業に向いてない";
		$result["comment1"] = "優れている";
		$result["comment2"] = "適性に合うようである";
		$result["comment3"] = "成長の可能性が見える";
		$result["comment4"] = "適性が合わないようである";
		$resultDatas[3] = $result;

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
		$questionIdxsByScore = array();
		for ($i = 1; $i <= 5; $i++) {
			$questionIdxsByScore[$i] = array();
		}

		$totalPoint = 0;
		for ($i = 0; $i < $question_num; $i++) {
			$isEmpty = true;
			for ($j = 1; $j <= 5; $j++) {
				if (!empty($beforeQuestionIdxsByScore[$j])) { $isEmpty = false; }
			}
			if ($isEmpty) { return $questionIdxsByScore; }

			do { $point = rand(1, 5); }
			while (empty($beforeQuestionIdxsByScore[$point]));

			$rndIdx = array_rand($beforeQuestionIdxsByScore[$point]);
			$questionIdxsByScore[$point][$rndIdx] = $beforeQuestionIdxsByScore[$point][$rndIdx];
			unset($beforeQuestionIdxsByScore[$point][$rndIdx]);
			$totalPoint += $point;

			while ($totalPoint > 100) {
				$before = array();
				$after = array();

				for ($j = 5; $j >= 2; $j--) {
					if (!empty($questionIdxsByScore[$j])) {
						$before["point"] = $j;
						$before["index"] = array_rand($questionIdxsByScore[$j]);
						$before["data"] = $questionIdxsByScore[$j][$before["index"]];
						unset($questionIdxsByScore[$before["point"]][$before["index"]]);
						break;
					}
					if ($j == 2) { return $questionIdxsByScore; }
				}

				for ($j = 1; $j <= $before["point"]; $j++) {
					if (!empty($beforeQuestionIdxsByScore[$j])) {
						$after["point"] = $j;
						$after["index"] = array_rand($beforeQuestionIdxsByScore[$j]);
						$after["data"] = $beforeQuestionIdxsByScore[$j][$after["index"]];
						unset($beforeQuestionIdxsByScore[$after["point"]][$after["index"]]);
						break;
					}
					if ($j == $before["point"]	) { return $questionIdxsByScore; }
				}

				$questionIdxsByScore[$after["point"]][$after["index"]] = $after["data"];
				$beforeQuestionIdxsByScore[$before["point"]][$before["index"]] = $before["data"];
				$totalPoint = $totalPoint - $before["point"] + $after["point"];
			}
		}

		return $questionIdxsByScore;
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
}