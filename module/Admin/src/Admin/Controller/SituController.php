<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Admin\Model\MailRequest;

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

	public function inputAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断状況管理", "診断者登録"];
		$index = $this->params()->fromRoute("index");
		$post = $this->params()->fromPost();
		$editDatas  = (isset($post['editDatas']) && $post['editDatas'] !='')  ? $post['editDatas'] : '';

		// print_r(($post['id']));
		$recordTb = $this->getServiceLocator()->get("RecordTable-Admin");
		$applicantTb = $this->getServiceLocator()->get("ApplicantTable-Admin");
		$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");
		$situTb = $this->getServiceLocator()->get("situTable");
		$managerInfo=$situTb->readByManagerInfo();
		$managerArray=[$managerInfo["id"], $managerInfo["password"],$managerInfo["name"],$managerInfo["smtp_password"]];

		$recordData = $recordTb->ReadByIdx($index);

		$applicantData = $applicantTb->ReadByIdx($recordData["applicant_idx"]);
		// $recordData = array_merge($applicantData, $recordData);


		if (($recordData["diagnosis_code"]) != null) {
			$diagnosisData = $diagnosisTb->ReadByCode($recordData["diagnosis_code"]);
			$recordData = array_merge($diagnosisData, $recordData);
		}
		$diagnosisData = $diagnosisTb->ReadByCode($recordData["diagnosis_code"]);
		$datas["optionDatas"] = $this->GetOptionDatasForInput();

		$class1st=$situTb->getclass1st();
		$class2nd=$situTb->getclass2nd();
		$applicantInfo = $situTb->readById($index);


		$datas["class1st"] = $class1st;
		$datas["class2nd"] = $class2nd;

		$datas["applicantArray"] = $applicantData;
		$datas["recordArray"] = $recordData;
		$datas["diagnosisArray"] = $diagnosisData;

		if($editDatas == "btn_submit"){
			$recordlWhere['idx']=$recordData['idx'];
			$applicantWhere['idx']=$applicantData['idx'];
			$applicantSet['email']=$post['email'];
			$applicantSet['password']=$post['password'];
			$applicantSet['name']=$post['name'];
			$applicantSet['kana']=$post['kana'];
			$applicantSet['birth']=$post['birth'];
			$recordSet['case']=$post['case'];
			$recordSet['education']=$post['education'];
			$applicantSet['career']=$post['career'];
			$applicantSet['certificates']=$post['certificates'];
			$applicantSet['other']=$post['other'];
			$recordSet['major']=$post['major'];
			$recordSet['skill']=$post['skill'];
			$recordSet['class1st']=$post['class1st'];
			$recordSet['class2nd']=$post['class2nd'];
			$datas["recordArray"] = $recordData;
			$datas["applicantArray"] = $applicantData;
			
			$situTb->updateRecordInfo($recordlWhere, $recordSet);	
			$situTb->updateApplicantInfo($applicantWhere, $applicantSet);	
			$recentPassword =  $situTb->readById($applicantInfo);

			$this->mailByRequest($recordData,$managerArray,$recentPassword);

			echo "
			<script>
			alert('依頼が 完了しました。')
			self.location.href='/admin/situation/list';
			</script>
			";	
	
		}

	

		return $this->SetViewModel($datas, "/situation/situation_input.phtml");
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
		$managerInfo=$situTb->readByManagerInfo();
		$managerArray=[$managerInfo["id"], $managerInfo["password"],$managerInfo["name"],$managerInfo["smtp_password"]];

		$recordData = $recordTb->ReadByIdx($index);

		$applicantData = $applicantTb->ReadByIdx($recordData["applicant_idx"]);
		$recordData = array_merge($applicantData, $recordData);


		if (($recordData["diagnosis_code"]) != null) {
			$diagnosisData = $diagnosisTb->ReadByCode($recordData["diagnosis_code"]);
			$recordData = array_merge($diagnosisData, $recordData);
		}
		$diagnosisData = $diagnosisTb->ReadByCode($recordData["diagnosis_code"]);
		$datas["optionDatas"] = $this->GetOptionDatasForInput();

		$class1st=$situTb->getclass1st();
		$class2nd=$situTb->getclass2nd();
		$applicantInfo = $situTb->readById($index);


		$datas["class1st"] = $class1st;
		$datas["class2nd"] = $class2nd;

		$datas["applicantArray"] = $applicantData;
		$datas["recordArray"] = $recordData;
		$datas["diagnosisArray"] = $diagnosisData;

		if($editDatas == "btn_submit"){
			$recordlWhere['idx']=$recordData['idx'];
			$applicantWhere['idx']=$applicantData['idx'];
			$applicantSet['email']=$post['email'];
			$applicantSet['password']=$post['password'];
			$applicantSet['name']=$post['name'];
			$applicantSet['kana']=$post['kana'];
			$applicantSet['birth']=$post['birth'];
			$recordSet['case']=$post['case'];
			$recordSet['education']=$post['education'];
			$applicantSet['career']=$post['career'];
			$applicantSet['certificates']=$post['certificates'];
			$applicantSet['other']=$post['other'];
			$recordSet['major']=$post['major'];
			$recordSet['skill']=$post['skill'];
			$recordSet['class1st']=$post['class1st'];
			$recordSet['class2nd']=$post['class2nd'];
			$datas["recordArray"] = $recordData;
			$datas["applicantArray"] = $applicantData;
			
			$situTb->updateRecordInfo($recordlWhere, $recordSet);	
			$situTb->updateApplicantInfo($applicantWhere, $applicantSet);	
			$recentPassword =  $situTb->readById($applicantInfo);

			$this->mailByRequest($recordData,$managerArray,$recentPassword);

			echo "
			<script>
			alert('依頼が 完了しました。')
			self.location.href='/admin/situation/list';
			</script>
			";	
	
		}

		return $this->SetViewModel($datas, "/situation/situation_edit.phtml");
	}

	function mailByRequest($recordData,$managerArray,$recentPassword){
		$mail = new MailRequest();

		// 기본 메일 전송 관련 설정 로드
		$param['config']=$this->getConfig();
		// 메일 제목 지정 (일반적으로 DB에 메일폼 테이블을 만들어서 그것을 가져와서 아래의 title contents에 넣지만, 이건 샘플이므로 간단히.)
		// 사람마다 변환해야 할 부분은 {{이렇게}} 메일폼에 넣어놓는다.

		$param['title']="{$recordData["name"]}様、株式会社ジエンジサービスから、ITスキル診断依頼が到着しています。";
		$param["content"] = "以下URLより「ITスキル診断サイト」にログインし診断を行ってください。\n\nログインID：{$recordData["email"]}\nログインPWD：{$recentPassword["password"]}\n\n＜ITスキル診断URL＞\nhttp://gngitskill:84/applicant/login\n\n\n※このメールに返信しないでください。";
	
		// 사람이름이나, URL등 고유하게 변경해야 하는 것은 이렇게 처리한다.
		// 메일 제목과 내용 부분 모두 변환처리.
		$param['title']=str_replace("{{user_name}}","担当者",$param['title']);
		// print_r($param['title']);
		// $param['content']=str_replace("{{user_name}}","変換する試験受け者名",$param['content']);
		$param['content']=str_replace("{{URL}}","テスト",$param['content']);
	

		// 수신자 이메일과 이름 설정
		$param['managerEmail']=$managerArray[0];
		$param['email']=$recordData["email"];;
		$param['password']="$managerArray[1]";
		$param['name']="$managerArray[2]";
		$param['smtp_password']="$managerArray[3]";
	
		// 전송
		$result = $mail->mailsender($param);
		// $result = $this->getServiceLocator()->get("mailsender");
	
		$result_row = $result['transport']->getConnection()->getResponse();
	
		$results = str_replace("\r","",str_replace("\n","",str_replace(" ","",$result_row[0])));
		switch(substr(strtolower($results),0,5)){
			// 250ok 가 나오면 전송 의뢰 성공이다.
				case "250ok":
					$status = 'OK';
						break;
				// 그외의 것은 모두 실패로 처리한다.
				default:
					$status = 'FALSE';
						break;
		}
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
	function GetOptionDatasForInput() {
		$optionTb = $this->getServiceLocator()->get("OptionTable");
		$beforeOptionDatas = iterator_to_array($optionTb->ReadValid());

		$afterOptionDatas = array();
		$class1stDatas = array();
		$class2ndDatas = array();
		$other = array();
		foreach ($beforeOptionDatas as $data) {
			if ($data["type"] == "status") { continue; }
			if ($data["type"] == "level") { continue; }
			if ($data["text"] == "その他") {
				$other = $data;
				continue;
			}
			if ($data["type"] == "class1st") {
				$class1stDatas[$data["idx"]] = $data["text"];
			}
			if ($data["type"] == "class2nd") {
				array_push($class2ndDatas, $data);
				continue;
			}

			if (!isset($afterOptionDatas[$data["type"]])) {
				$afterOptionDatas[$data["type"]] = array();
			}
			array_push($afterOptionDatas[$data["type"]], $data);
		}

		array_push($afterOptionDatas["class1st"], $other);

		foreach ($class2ndDatas as $data) {
			if (!isset($afterOptionDatas["class2nd"][$data["class_upper"]])) {
				$afterOptionDatas["class2nd"][$data["class_upper"]] = array();
			}
			array_push($afterOptionDatas["class2nd"][$data["class_upper"]], $data);
		}

		foreach ($class2ndDatas as $data) {
			$class_upper = $data["class_upper"];
			
			// Debugging: Check if class_upper exists in $class1stDatas
			if (!isset($class1stDatas[$class_upper])) {
				// echo "Notice: Undefined index $class_upper in \$class1stDatas\n";
				continue; // Skip this iteration if the index is not set
			}
		
			$class1stValue = $class1stDatas[$class_upper];
		
			// Check if $class1stValue is set in $afterOptionDatas["class2nd"]
			if (!isset($afterOptionDatas["class2nd"][$class1stValue])) {
				$afterOptionDatas["class2nd"][$class1stValue] = array();
			}
		
			array_push($afterOptionDatas["class2nd"][$class1stValue], $data);
		}
		// Optional: Debugging output

		// print_r($afterOptionDatas);
		// print_r($afterOptionDatas["class2nd"][$class1stDatas[$data["class_upper"]]]);
		// exit;

		return $afterOptionDatas;
	}	

	public function getConfig(){
		if(isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT']!=''){
				$droot = $_SERVER['DOCUMENT_ROOT'];
		}else{
				$droot = "abc";
		}
		if(is_file($droot.'/../config/autoload/local.php')){
				$config = require $droot.'/../config/autoload/local.php';
		}else{
				$config = require $droot.'/../config/autoload/global.php';
		}
		return $config;
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