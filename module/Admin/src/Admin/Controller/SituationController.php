<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Admin\Model\MailRequest;

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
			$sqlWhere = array();
			if (isset($query["name"])) {
				$applicantTb = $this->getServiceLocator()->get("ApplicantTable-Admin");
				try { $applicantDatas = $applicantTb->ReadByName($query["name"]); }
				catch (\Exception $e) { print_r($e->getMessage()); exit; }

				foreach ($applicantDatas as $data) {
					$sqlWhere[] = $data["idx"];
				}
			}

			$sqlOrder["apply_date"] = "DESC";
			if (isset($query["align"])) {
				unset($sqlOrder["apply_date"]);
				$sqlOrder[explode("-", $query["align"])[0]] = explode("-", $query["align"])[1];
			}

			try { $newRecordDatas = $recordTb->ReadNewListBySearchnOffsetnAlign($sqlWhere, $offset, $sqlOrder); }
			catch (\Exception $e) { print_r($e->getMessage()); exit; }

			if (count($newRecordDatas) <= 10) {
				$offset += count($newRecordDatas);
				$limit = 10 - count($newRecordDatas);
				try { $restRecordDatas = $recordTb->ReadRestListBySearchnOffsetnLimitnAlign($sqlWhere, $offset, $limit, $sqlOrder); }
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

	function requestAction() {
		$idxs = $this->params()->fromPost("idxs");
		$recordIdxs = explode(",", $idxs);

		$applicantTb = $this->getServiceLocator()->get("ApplicantTable-Admin");
		$recordTb = $this->getServiceLocator()->get("RecordTable-Admin");
		$adminTb = $this->getServiceLocator()->get("AdminTable");

		$PICDatas = $adminTb->ReadPIC();
		foreach ($PICDatas as $adminData) {
			foreach ($recordIdxs as $idx) {
				$recordData = $recordTb->ReadByIdx($idx);
				$applicantData = $applicantTb->ReadByIdx($recordData["applicant_idx"]);

				$skillText = "無";
				if ($recordData["skill"] == 0) { $skillText = "有"; } 
		
				$caseText = "中途（経歴職）";
				if($recordData["case"] == 0){ $caseText = "新卒"; }

				$this->mailByRequest($adminData, $applicantData);
				$this->mailByAdmin($applicantData, $skillText, $caseText, $adminData, $applicantData);
			}
		}

		return "success";
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


	function mailByRequest($managerArray,$recentPassword){
		$mail = new MailRequest();

		// 기본 메일 전송 관련 설정 로드
		$param['config']=$this->getConfig();
		// 메일 제목 지정 (일반적으로 DB에 메일폼 테이블을 만들어서 그것을 가져와서 아래의 title contents에 넣지만, 이건 샘플이므로 간단히.)
		// 사람마다 변환해야 할 부분은 {{이렇게}} 메일폼에 넣어놓는다.

		$param['title']="{$recentPassword["name"]}様、株式会社ジエンジサービスから、ITスキル診断依頼が到着しています。";
		$param["content"] = "以下URLより「ITスキル診断サイト」にログインし診断を行ってください。\n\nログインID：{$recentPassword["email"]}\nログインPWD：{$recentPassword["password"]}\n\n＜ITスキル診断URL＞\nhttp://gngitskill:84/applicant/login\n\n\n※このメールに返信しないでください。";
	
		// 사람이름이나, URL등 고유하게 변경해야 하는 것은 이렇게 처리한다.
		// 메일 제목과 내용 부분 모두 변환처리.
		$param['title']=str_replace("{{user_name}}","担当者",$param['title']);
		// print_r($param['title']);
		// $param['content']=str_replace("{{user_name}}","変換する試験受け者名",$param['content']);
		$param['content']=str_replace("{{URL}}","テスト",$param['content']);
	

		// 수신자 이메일과 이름 설정
		$param['managerEmail']=$managerArray[0];
		$param['email']=$recentPassword["email"];;
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

    function mailByAdmin($arr,$skillText,$caseText,$managerArray,$applicantInfo){
      $mail = new MailRequest();
      

      // 기본 메일 전송 관련 설정 로드
      $param['config']=$this->getConfig();
      // 메일 제목 지정 (일반적으로 DB에 메일폼 테이블을 만들어서 그것을 가져와서 아래의 title contents에 넣지만, 이건 샘플이므로 간단히.)
      // 사람마다 변환해야 할 부분은 {{이렇게}} 메일폼에 넣어놓는다.
      $param['title']="{{user_name}}様、新しい試験診断の申し込みがあります。";
      $param["content"] = "以下の申込者の情報をご参照ください。\n\nお名前（漢字）：{$arr["name"]}\nお名前（カナ）：{$arr["kana"]}\n応募区分：{$caseText}\nITスキル：{$skillText}\n\n診断者ページ：http://gngitskill:84/admin/situation/detail/{$applicantInfo["idx"]}";
    
      // 사람이름이나, URL등 고유하게 변경해야 하는 것은 이렇게 처리한다.
      // 메일 제목과 내용 부분 모두 변환처리.
      $param['title']=str_replace("{{user_name}}","申し込み担当者",$param['title']);
      // print_r($param['config']);
      // $param['content']=str_replace("{{user_name}}","変換する試験受け者名",$param['content']);
      $param['content']=str_replace("{{URL}}","テスト",$param['content']);
    
    
      // 수신자 이메일과 이름 설정
      $param['email']=$managerArray[0];
      $param['password']="$managerArray[1]";
      $param['name']="$managerArray[2]";
      $param['smtp_password']="$managerArray[3]";
      // 전송
      $result = $mail->mailAdmin($param);
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
}