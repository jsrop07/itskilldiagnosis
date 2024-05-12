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
		header("Location: ./diagnosis/list");
		exit;
	}

	public function listAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断書管理"];

		// Number of data to output on one page
		$printDataNum = 10;
		$datas = $this->GetOptionDatasForInput($datas);

		// Get Current Page
		$page = $this->params()->fromQuery("page", 1);

		// Get Query Except page
		$query  = $this->params()->fromQuery();
		unset($query["page"]);

		$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");

		$totalDiagnosisDatas = "";
		$paginationData = "";
		if (!empty($query)) {
			try {
				$totalDiagnosisDatas = $diagnosisTb->ReadListByOption($query);
				$paginationData = $diagnosisTb->GetListByOption($query);
			} catch (\Exception $e) {
				print_r($e->getMessage());
				exit;
			}
			$datas["searchData"] = $query;
		}
		else {
			try {
				$totalDiagnosisDatas = $diagnosisTb->ReadAllList();
				$paginationData = $diagnosisTb->GetAllList();
			} catch (\Exception $e) {
				print_r($e->getMessage());
				exit;
			}
		}

		$datas["totalData"] = count($totalDiagnosisDatas);

		// Extract output datas and Add numbering
		if (!empty($totalDiagnosisDatas)) {
			$diagnosisDatas = array();
			$startIdx = ($page - 1) * $printDataNum;
			$endIdx = ($page * $printDataNum);
			
			for ($i = 0; $startIdx + $i < $endIdx; $i++) {
				if (!isset($totalDiagnosisDatas[$startIdx + $i])) break;

				$diagnosisDatas[$i] = $totalDiagnosisDatas[$startIdx + $i];
				$diagnosisDatas[$i]["num"] = count($totalDiagnosisDatas) - ($startIdx + $i);
			}

			$datas["diagnosisDatas"] = $diagnosisDatas;
		}

		$datas = $this->GetOptionDatas($datas);

		$vm = $this->SetViewModel($datas, "/diagnosis/diagnosis_list.phtml");
		$vm->noticelist = $paginationData;
		$vm->noticelist->setCurrentPageNumber($page);
		$vm->noticelist->setItemCountPerPage($printDataNum);
		return $vm;
	}
	
	/** When you click 新規登録 button on 一覧 page */
	public function inputAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断書管理", "診断書登録"];
		$datas["title"] = "診断書登録";

		// Check return from 登録確認　page
		$post = $this->params()->fromPost();
		if (isset($post["code"])) {
			$datas["diagnosisData"] = $post;
		} else {
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

		$datas = $this->GetOptionDatasForInput($datas);
		return $this->SetViewModel($datas, "/diagnosis/diagnosis_input.phtml");
	}

	/** When you click 登録 button on 診断書登録 page */
	public function confirmAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "診断書登録" ,"登録確認"];
		$datas["title"] = "診断書確認";

		$datas["diagnosisData"] = $this->params()->fromPost();
		$datas = $this->GetOptionDatas($datas);

		return $this->SetViewModel($datas, "/diagnosis/diagnosis_confirm.phtml");
	}

	/** When you choose list data on 問題一覧 page */
	public function detailAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断書管理", "診断書詳細"];
		$datas["title"] = "診断書詳細";

		$datas = $this->GetOptionDatas($datas);

		// Get Code
		$code = $this->params()->fromRoute("index");

		$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");
		try { $datas["diagnosisData"] = $diagnosisTb->ReadByCode($code); }
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

	public function readAction() {
		$post = $this->params()->fromPost();

		$sqlWhere = $post;

		unset($sqlWhere["question_num"]);
		unset($sqlWhere["time_limit"]);

		$totalQuestionDatas = $this->ReadDataForDiagnosis($sqlWhere);

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

			// if ($i + 1 == $post["question_num"] && $totalPoint != 100) {
			// 	$point = 100 - $totalPoint + 1;

			// 	$index = array_rand($questionDatas[1]);
			// 	unset($questionDatas[1][$index]);

			// 	$index = array_rand($tempQuestionDatas[$point]);
			// 	$questionDatas[$point][$index] = $tempQuestionDatas[$point][$index];
			// }
		}
		die($this->PointQuestionsToJson($questionDatas));
	}

	function createAction() {
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
		$sqlValue["date_create"] = date("Y-m-d H:i:s");
		$sqlValue["status"] = "保存";
		$sqlValue["case"] = 22;

		$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");
		try { $diagnosisTb->createDiagnosis($sqlValue); }
		catch (\Exception $e) { die($e->getMessage()); }

		die ("success");
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

	/** Add optionDatas in $datas
	 * @param mixed $datas array #ViewModel($datas)
	 * @return mixed $datas add optionDatas["index" => "text"]
	*/
	function GetOptionDatas($datas) {
		$optionTb = $this->getServiceLocator()->get("OptionTable");
		$optionDatas = iterator_to_array($optionTb->ReadAll());

		foreach ($optionDatas as $data) {
			$datas["optionDatas"][$data["idx"]] = $data["text"];
		}

		return $datas;
	}

	/** Add optionDatas for input in $datas
	 * @param mixed $datas array #ViewModel($datas)
	 * @return mixed $datas add optionDatas["type"] = array()
	*/
	function GetOptionDatasForInput($datas) {
		$optionTb = $this->getServiceLocator()->get("OptionTable");
		$optionDatas = iterator_to_array($optionTb->ReadValid());

		if (!isset($datas["optionDatas"])) { $data["optionDatas"] = array(); }
		$other = array();
		foreach ($optionDatas as $data) {
			if ($data["type"] == "status") { continue; }
			if ($data["type"] == "level") { continue; }
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
		$datas["optionDatas"]["level"] = array();
		array_push($datas["optionDatas"]["level"], $optionTb->ReadByText("初級"));
		array_push($datas["optionDatas"]["level"], $optionTb->ReadByText("中級"));
		array_push($datas["optionDatas"]["level"], $optionTb->ReadByText("高級"));

		return $datas;
	}

	/** Make QuestionDatas by Point
	 * @param array $whereDatas array[class1st, class2nd, level]
	 * @return mixed $questionDatas
	*/
	function ReadDataForDiagnosis($sqlWhere) {
		$questionTb = $this->getServiceLocator()->get("QuestionTable");

		$questionDatas = array();
		for ($i = 1; $i <= 5; $i++) {
			$sqlWhere["point"] = $i;
			$questionDatas[$i] = $questionTb->ReadListByOption($sqlWhere);
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
}