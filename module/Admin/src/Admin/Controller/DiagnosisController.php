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
		print_r("Diagnosis Index");
		exit;
	}

	public function listAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断書管理"];

		$page = $this->params()->fromQuery("page", 1);
		$printDataNum = 10;	// Number of data to output on one page

		$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");

		try {
			$totalDiagnosisDatas = $diagnosisTb->ReadAllList();
			$paginationData = $diagnosisTb->GetAllList();
		} catch (\Exception $e) {
			die($e->getMessage());
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
		}

		$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");

		$datas = $this->GetOptionDatasForInput($datas);
		return $this->SetViewModel($datas, "/diagnosis/diagnosis_input.phtml");
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

	public function registAction() {
		$datas["breadcrumbData"] = ["ITスキル診断書管理", "診断書登録"];
		$datas["title"] = "診断書登録";

		return $this->SetViewModel($datas, "/diagnosis/diagnosis_input.phtml");
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

		$datas["optionDatas"] = array();
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
}