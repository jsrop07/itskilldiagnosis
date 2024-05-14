<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class SituController extends AbstractActionController {
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
		print_r("ss"); exit;

	}

	public function editAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断状況管理", "診断状況詳細", "診断状況修正"];
		$index = $this->params()->fromRoute("index");
		$post = $this->params()->fromPost();
		$editDatas  = (isset($post['editDatas']) && $post['editDatas'] !='')  ? $post['editDatas'] : '';

		// print_r(($post['id']));
		$recordTb = $this->getServiceLocator()->get("RecordTable-Admin");
		$applicantTb = $this->getServiceLocator()->get("ApplicantTable-Admin");
		$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");
		$situTb = $this->getServiceLocator()->get("situTable");

		$recordData = $recordTb->ReadByIdx($index);

		$applicantData = $applicantTb->ReadByIdx($recordData["applicant_idx"]);
		$recordData = array_merge($applicantData, $recordData);

		if (($recordData["diagnosis_code"]) == null) {
			$diagnosisData = $diagnosisTb->ReadByCode($recordData["diagnosis_code"]);
			$recordData = array_merge($diagnosisData, $recordData);
		}
		$diagnosisData = $diagnosisTb->ReadByCode($recordData["diagnosis_code"]);

		$datas = $this->GetOptionDatas($datas);
		$datas["applicantArray"]=$applicantData;
		$datas['recordArray']=$recordData;
		$datas['diagnosisArray']=$diagnosisData;
		// print_r(($recordData['idx']));

		// if($editDatas == "btn_submit"){
		// 	$sqlWhere['idx']=$recordData['idx'];
		// 	// $sqlSet['email']=$applicantData['email'];
		// 	// $sqlSet['name']=$applicantData['name'];
		// 	// $sqlSet['kana']=$applicantData['kana'];
		// 	$sqlSet['case']=$editDatas['case'];
		// 	$sqlSet['education']=$recordData['education'];
		// 	// $sqlSet['career']=$applicantData['career'];
		// 	// $sqlSet['certificates']=$applicantData['certificates'];
		// 	// $sqlSet['other']=$applicantData['other'];
		// 	$sqlSet['major']=$recordData['major'];
		// 	$sqlSet['skill']=$recordData['skill'];
		// 	// $sqlSet['class1st']=$recordData['class1st'];
		// 	// $sqlSet['class2nd']=$recordData['class2nd'];
		// 	print_r($recordData['idx']);


		// 	$situTb->updateExam($sqlWhere, $sqlSet);			
		// 	echo "
		// 	<script>
		// 	self.location.href='/applicant/examclear';
		// 	</script>
		// 	";	
	
		// }

		return $this->SetViewModel($datas, "/situation/situation_edit.phtml");
	}

	/** Set Layout & Make ViewModel with datas and template 
	 * @param mixed $datas array #ViewModel($datas)
	 * @param mixed $template string #setTemplate($template) 
	 * @return ViewModel
	*/
	function SetViewModel($datas, $template) {
		$this->layout("/layout/situ_layout.phtml");

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
}