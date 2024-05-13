<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class SituationController extends AbstractActionController {
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
		header("Location: ./situation/list");
		exit;
	}

	public function listAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断状況管理"];

		// Number of data to output on one page
		$printDataNum = 10;
		$datas = $this->GetOptionDatasForInput($datas);

		// Get Current Page
		$page = $this->params()->fromQuery("page", 1);

		// Get Query Except page
		$query  = $this->params()->fromQuery();
		unset($query["page"]);

		$recordTb = $this->getServiceLocator()->get("RecordTable-Admin");

		$totalRecordDatas = "";
		$paginationData = "";
		if (!empty($query)) {
			try {
				$totalNewRecordDatas = $recordTb->ReadAllNewList();
				$totalRestRecordDatas = $recordTb->ReadAllRestList();
				$totalRecordDatas = array_merge($totalNewRecordDatas, $totalRestRecordDatas);
				$paginationData = $recordTb->GetAllList();
			} catch (\Exception $e) {
				print_r($e->getMessage());
				exit;
			}
			$datas["searchData"] = $query;
		}
		else {
			try {
				$totalNewRecordDatas = $recordTb->ReadAllNewList();
				$totalRestRecordDatas = $recordTb->ReadAllRestList();
				$totalRecordDatas = array_merge($totalNewRecordDatas, $totalRestRecordDatas);
				$paginationData = $recordTb->GetAllList();
			} catch (\Exception $e) {
				print_r($e->getMessage());
				exit;
			}
		}

		$datas["totalApply"] = count($recordTb->ReadApplyData());
		$datas["totalRequest"] = count($recordTb->ReadRequestData());
		$datas["totalData"] = count($totalRecordDatas);

		$applicantTb = $this->getServiceLocator()->get("ApplicantTable-Admin");
		$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");
		// Extract output datas and Add numbering
		if (!empty($totalRecordDatas)) {
			$recordDatas = array();
			$startIdx = ($page - 1) * $printDataNum;
			$endIdx = ($page * $printDataNum);

			for ($i = 0; $startIdx + $i < $endIdx; $i++) {
				if (!isset($totalRecordDatas[$startIdx + $i])) { break; }

				$recordData = $totalRecordDatas[$startIdx + $i];

				$applicantData = $applicantTb->ReadByIdx($recordData["applicant_idx"]);
				$recordData = array_merge($applicantData, $recordData);

				if (($recordData["diagnosis_code"]) != null) {
					$diagnosisData = $diagnosisTb->ReadByCode($recordData["diagnosis_code"]);
					$recordData = array_merge($diagnosisData, $recordData);
				}

				if ($recordData["request_date"] == null) { $recordData["status"] = "新規"; }
				else if ($recordData["execute_date"] == null) { $recordData["status"] = "診断"; }
				else { $recordData["status"] = "終了"; }

				$recordData["num"] = count($totalRecordDatas) - ($startIdx + $i);

				$recordDatas[$i] = $recordData;
			}

			$datas["recordDatas"] = $recordDatas;
		}

		$datas = $this->GetOptionDatas($datas);

		$vm = $this->SetViewModel($datas, "/situation/situation_list.phtml");
		$vm->noticelist = $paginationData;
		$vm->noticelist->setCurrentPageNumber($page);
		$vm->noticelist->setItemCountPerPage($printDataNum);
		return $vm;
	}

	/** When you choose list data on 診断者一覧 page */
	public function detailAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断状況管理", "診断状況詳細"];
		$index = $this->params()->fromRoute("index");

		$recordTb = $this->getServiceLocator()->get("RecordTable-Admin");
		$recordData = $recordTb->ReadByIdx($index);
		if (($recordData["diagnosis_code"]) == null) {
			header("Location: ../edit/" . $index);
			exit;
		}

		$applicantTb = $this->getServiceLocator()->get("ApplicantTable-Admin");
		$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");

		$applicantData = $applicantTb->ReadByIdx($recordData["applicant_idx"]);
		$recordData = array_merge($applicantData, $recordData);

		$diagnosisData = $diagnosisTb->ReadByCode($recordData["diagnosis_code"]);
		$recordData = array_merge($diagnosisData, $recordData);
		
		$datas = $this->GetOptionDatas($datas);

		$datas["recordData"] = $recordData;

		return $this->SetViewModel($datas, "/situation/situation_detail.phtml");
	}

	/** When you click 新規登録 button on 診断者一覧 page */
	public function inputAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断状況管理", "診断者登録"];

		$password = "";
		for ($i = 0; $i < 8; $i++) {
			$rand = rand(1, 3);

			switch ($rand) {
				case 1:
					$password .= rand(0, 9);
					break;
				case 2:
					$password .= chr(rand(65, 90));
					break;
				case 3:
					$password .= chr(rand(97, 122));
					break;
			}
		}
		$datas["password"] = $password;

		return $this->SetViewModel($datas, "/situation/situation_input.phtml");
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

			foreach ($questionDatas[$i] as $idx => $data) {
				foreach ($data as $index => $value) {
					if ($index == "idx") { continue; }
					if ($index == "title") { continue; }
					if ($index == "type") { continue; }
					if ($index == "point") { continue; }
					unset($data[$index]);
				}
				$questionDatas[$i][$idx] = $data;
			}
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