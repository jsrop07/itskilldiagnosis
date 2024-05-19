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
		$paginationData = $recordTb->GetAllList();

		$recordDatas = array();
		$offset = ($page - 1) * 10;
		if (!empty($query)) {
			if ($query["align"])
			$sqlOrder[explode("-", $query["align"])[0]] = explode("-", $query["align"])[1];
			try { $newRecordDatas = $recordTb->ReadNewListBySearchnOffsetnAlign($offset, $sqlOrder); }
			catch (\Exception $e) { print_r($e->getMessage()); exit; }

			if (count($newRecordDatas) <= 10) {
				$offset += count($newRecordDatas);
				$limit = 10 - count($newRecordDatas);
				try { $restRecordDatas = $recordTb->ReadRestListBySearchnOffsetnLimitnAlign($offset, $limit, $sqlOrder); }
				catch (\Exception $e) { print_r($e->getMessage()); exit; }
				
				$recordDatas = array_merge($newRecordDatas, $restRecordDatas);
			}
			else {
				$recordDatas = $newRecordDatas;
			}
			$datas["searchDatas"] = $query;
		}
		else {
			try { $newRecordDatas = $recordTb->ReadNewListByOffset($offset); }
			catch (\Exception $e) { print_r($e->getMessage()); exit; }

			if (count($newRecordDatas) <= 10) {
				$offset += count($newRecordDatas);
				$limit = 10 - count($newRecordDatas);
				try { $restRecordDatas = $recordTb->ReadRestListByOffsetnLimit($offset, $limit); }
				catch (\Exception $e) { print_r($e->getMessage()); exit; }

				$recordDatas = array_merge($newRecordDatas, $restRecordDatas);
			}
			else {
				$recordDatas = $newRecordDatas;
			}
		}

		try {
			$datas["totalApply"] = $recordTb->CountApplyData();
			$datas["totalRequest"] = $recordTb->CountRequestData();
			$datas["totalData"] = $recordTb->CountRecordData();
		} catch (\Exception $e) {
			print_r($e->getMessage());
			exit;
		}

		$applicantTb = $this->getServiceLocator()->get("ApplicantTable-Admin");
		$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");
		// Extract output datas and Add numbering

			foreach ($recordDatas as $index => $data) {
				try { $applicantData = $applicantTb->ReadByIdx($data["applicant_idx"]); }
				catch (\Exception $e) { print_r($e->getMessage()); exit; }
				$data = array_merge($applicantData, $data);

				if (($data["diagnosis_code"]) != null) {
					try { $diagnosisData = $diagnosisTb->ReadByCode($data["diagnosis_code"]); }
					catch (\Exception $e) { print_r($e->getMessage()); exit; }
					$data = array_merge($diagnosisData, $data);
				}

				if ($data["request_date"] == null) { $data["status"] = "新規"; }
				else if ($data["execute_date"] == null) { $data["status"] = "診断"; }
				else { $data["status"] = "終了"; }

				$data["num"] = $datas["totalData"] - (($page - 1) * 10) - $index;
				
				$recordDatas[$index] = $data;
			}

			$datas["recordDatas"] = $recordDatas;

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