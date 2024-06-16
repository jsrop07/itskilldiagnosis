<?php

namespace Applicant\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\Controller\Plugin\Redirect;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Session\Container;
use Applicant\Model\MailSender;


class ApplicantController extends AbstractActionController
{
	public function indexAction() {
		header("Location: applicant/login");
		exit;
	}

  public function applicationAction()
  {
	$this->layout("layout/applicant/application_layout");
	$config=$this->getServiceLocator()->get('config');
	//モデル連動
	$tbl=$this->getServiceLocator()->get('ApplicationTable');
	$class2nd=$tbl->getclass2nd();
	$class1st=$tbl->getclass1st();
	$managerInfo=$tbl->readByManagerInfo();
	$managerArray=[$managerInfo["id"], $managerInfo["password"],$managerInfo["name"],$managerInfo["smtp_password"]];
	$datas["optionDatas"] = $this->GetOptionDatasForInput();

	$p = $this->params()->fromPost();
	$mode =(isset($p['mode'])    &&   $p['mode'] !='')? $p['mode']:'';
	$viewModel = new ViewModel(['class2nd' => $class2nd,'class1st' => $class1st,  "optionDatas"=>$this->GetOptionDatasForInput(), 'p' => $p]);
	$viewModel->setTemplate("/applicant/application.phtml");

	if ($mode == 'btn_submit') {
		$email = $this->params()->fromPost('email');
		$name = $this->params()->fromPost('name');
		$kana = $this->params()->fromPost('kana');
		$gender = $this->params()->fromPost('gender');
		$birth = $this->params()->fromPost('birth');
		$case = $this->params()->fromPost('case');
		$education = $this->params()->fromPost('education');
		$major = $this->params()->fromPost('major');
		$skill = $this->params()->fromPost('skill');
		$class2nd = $this->params()->fromPost('class2nd');
		$class1st = $this->params()->fromPost('class1st');
		$career = $this->params()->fromPost('career');
		$certificates = $this->params()->fromPost('certificates');
		$other = $this->params()->fromPost('other');

		if ($skill == 0) {
			$skillText = '有';
		} else {
			$skillText = '無';
		}

		if($case == 0){
			$caseText = "新卒";
		}else{
			$caseText = "中途（経歴職）";
		}

		$arr = [
			'email' => $email,
			'name' => $name,
			'kana' => $kana,
			'gender' => $gender,
			'birth' => $birth,
			'case' => $case,
			'education' => $education,
			'major' => $major,
			'skill' => $skill,
			'class1st' => $class1st,
			'class2nd' => $class2nd,
			'career' => $career,
			'certificates' => $certificates,
			'other' => $other,
		];

		$tbl->insertAndUpdateApplication($arr);
		$applicantInfo = $tbl->getRecord();
		$this->mailByApplicantation($arr,$skillText,$caseText,$managerArray,$applicantInfo);

		echo "
		<script>
		self.location.href='/applicant/applicationclear';
		</script>
		";

		exit;
	}

	return $viewModel;
  }
	
	/* log
	作成：丁錫圓
	修正：丁錫圓
	修正日：24/06/04
	*/ 
  function duplicationAction(){
	$p = $this->params()->fromPost();
	$tbl=$this->getServiceLocator()->get('ApplicationTable');

	if (isset($p["email"])) {
	$result = $tbl->emailDuplicateCheck($p["email"]);
	die($result);
	}
	}
	// ここまで

  function applicationclearAction() {
	$this->layout("/applicant/applicationclear");
	}

  function mailByApplicantation($arr,$skillText,$caseText,$managerArray,$applicantInfo){
	$mail = new MailSender();
	$param['config']=$this->getConfig();
	
	$param['title']="ITスキル診断担当者様、新しい診断の申し込みがあります。";
	$param["content"] = "以下の申込者の情報をご参照ください。\n\n"
										. "お名前（漢字）：{{name}}\n"
										. "お名前（カナ）：{{kana}}\n"
										. "応募区分：{{case}}\n"
										. "ITスキル：{{skill}}\n\n"
										. "診断者ページ：{{url}}/situation/edit/{{idx}}";
										$param['content']=str_replace("{{name}}",$arr["name"],$param['content']);
										$param['content']=str_replace("{{kana}}",$arr["kana"],$param['content']);
										$param['content']=str_replace("{{case}}",$caseText,$param['content']);
										$param['content']=str_replace("{{skill}}",$skillText,$param['content']);
										$param['content']=str_replace("{{url}}",$param['config']['user-url']['admin'],$param['content']);
										$param['content']=str_replace("{{idx}}",$applicantInfo["idx"],$param['content']);
	
	$param['email']=$managerArray[0];
	$param['password']="$managerArray[1]";
	$param['name']="$managerArray[2]";
	$param['smtp_password']="$managerArray[3]";

	$result = $mail->mailsender($param);

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
	if ($status === 'FALSE') {
    echo "<script>alert('メールの送信に失敗しました。');</script>";
	}
  }


  public function loginAction()
  {
	$this->layout("layout/applicant/login_layout");
	//モデル連動
    $p = $this->params()->fromPost();
    if (isset($p["id"])) {
			$loginTbl = $this->getServiceLocator()->get("ApplicantLoginTable");
			$result = $loginTbl->login($p["id"], $p["password"]);

			if ($result == "success") {
				$session = new Container("applicant");
				$session["id"] = $p["id"];
			}
			die($result);
    }

	$session = new Container("applicant");
	if(isset($session['id']) && $session['id'] != '') {
		return $this->redirect()->toUrl("../applicant/exam");
	}

    $vm = new ViewModel();
    $vm->setTemplate("/applicant/login.phtml");
    return $vm;
  }


  public function examAction()
  {
	 // Set layout
	  $this->layout("layout/applicant/exam_layout");
	  $post = $this->params()->fromPost();
	  $submit_post = (isset($post['submit_post'])  &&   $post['submit_post'] !='')  ? $post['submit_post']  : '';
	  $answer_data = (isset($post['answer_data'])  &&   $post['answer_data'] !='')  ? $post['answer_data']  : '';


	  $session = new Container("applicant");
	  if (isset($session->id)) {
		  $emailId = $session->id;
		} else {
		$this->RedirectToLogin();		  
	  }

	  // Read applicant info
	  $applicantExamTbl = $this->getServiceLocator()->get("ApplicantExamTable");
	  $applicantInfo    = $applicantExamTbl->readById($emailId);
		$datas['emailId'] = $emailId;
		$situTbl= $this->getServiceLocator()->get("SituTable");
		$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");
		$recordIdx = $situTbl->getRecord();

	  // Read manager info
	  $managerInfo=$applicantExamTbl->readByManagerInfo();
	  $managerArray=[$managerInfo["id"], $managerInfo["password"],$managerInfo["name"],$managerInfo["smtp_password"]];

	  // Read $applicantinfo's to record
	  $examRecordInfo =  $applicantExamTbl->readByApplicantIdx($applicantInfo);
		$examRecordIdx = $examRecordInfo['idx'];
	  $datas['name']  =  $applicantInfo["name"];
		$datas['language'] = $examRecordInfo['language'];
		$datas['examIdx'] = $examRecordIdx;

		
		if (isset($post["idx"])) {
			$result = $applicantExamTbl->executeExam($post["idx"]);
			die(json_encode($result));
		}
		
		// Read record's diagnosis_code & Read selected diagnosis_code's info
	  $recordCode    = $examRecordInfo["diagnosis_code"];

	  // $diagnosisInfo = $applicantExamTbl->readByDiagnosisCode($recordCode);
		$diagnosisInfo = $diagnosisTb->ReadForRecordByCodenDate($recordCode, $examRecordInfo["diagnosis_date"]);
		// print_r($diagnosisInfo);
		// exit;
    // Read diagnosis's question_num field data & time_limit data
	  $datas["question_num"] = $diagnosisInfo["question_num"];
	  $datas["time_limit"]   = $diagnosisInfo["time_limit"];

	  // 정답값 비교하기
	  $findQuestionData = $applicantExamTbl->readByQuestion($post);

    //selected diagnosis_code's question_idxs data
	  $selectedQuestion_idxs = ['question_idxs'=>isset($diagnosisInfo['question_idxs'])? $diagnosisInfo['question_idxs']:null];

	  $matchedData=[];
	  foreach(explode(',',$selectedQuestion_idxs["question_idxs"]) as $value)
	  {
      foreach($findQuestionData as $data)
      {
        if($data['idx']==$value)
        {
          $matchedData[] = $data;    
        }
      }
	  }
    // Read diagnosis code's selected code's info
	  $datas["matchedData"]=$matchedData;
		// print_r($matchedData);
    //Read selected data's correct_idxs
	  $output = [];
		foreach ($matchedData as $item) {
				$output[] = $item['correct'];

			$outputPoint=[];
			foreach ($matchedData as $item) {
				$outputPoint[] = $item['point'];

			}
		}

		$result = implode(',', $output);
		$answerDataArray = explode(',',$answer_data);
		$resultArray = explode(',', $result);

		$length = count($answerDataArray);
		$get_point = 0;
		for ($i = 0; $i < $length; $i++) {
			if ($resultArray[$i] == $answerDataArray[$i]) { //if answer and correct answer is true
				$get_point+=$outputPoint[$i];// plus point 

			}
		}

		$percentPoint = ceil($get_point/$diagnosisInfo["point_total"]*100);

		$selectedRank = [];
		$resultPoints = explode(',', $diagnosisInfo["result_points"]);
		$resultTexts = explode(',', $diagnosisInfo["result_texts"]);
		$recordResults = explode(',', $diagnosisInfo["result_comments"]);

		foreach ($resultPoints as $index => $points) {
			// bring $resultPoints and $resultTexts's  each index value and add array 
			$selectedRank[] = [
				'result_points' => $points,
				'result_texts' => $resultTexts[$index],
				'result_comments' => $recordResults[$index]
			];
		}
		foreach ($selectedRank as $item) {
			if ($get_point > $selectedRank[0]['result_points']) {
				$recordRank="A";
				$recordExamResult=$selectedRank[0]['result_comments'];
			}
			elseif($get_point <= $selectedRank[0]['result_points'] && $get_point > $selectedRank[1]['result_points']){
				$recordRank="B";
				$recordExamResult=$selectedRank[1]['result_comments'];

			}
			elseif($get_point <= $selectedRank[1]['result_points'] && $get_point > $selectedRank[2]['result_points']){
				$recordRank="C";
				$recordExamResult=$selectedRank[2]['result_comments'];

			}
			else{
				$recordRank="D";
				$recordExamResult=$selectedRank[3]['result_comments'];

			}
		}
		$caseArray = array("新卒", "中途（経歴職）");
		if($examRecordInfo['case']==0){
			$caseText=$caseArray[0];
		}
		else{
			$caseText=$caseArray[1];

		}

		if($examRecordInfo['major']==""){
			$majorText="なし";
		}
		else{
			$majorText=$examRecordInfo['major'];
		}
		//submit
		if($submit_post=='btn_submit'){
			$sqlWhere["idx"] = $examRecordInfo['idx'];
			$sqlSet["answer_data"] = $answer_data;
			$sqlSet["get_point"] = $percentPoint;
			$sqlSet['rank']=$recordRank;
			$sqlSet['diagnosis_comment']=$recordExamResult;
			$sqlSet['solve_time']=$post['solveTime'];

			$applicantExamTbl->updateExam($sqlWhere, $sqlSet);			
			$examRecordRecent =  $applicantExamTbl->readByApplicantIdx($applicantInfo);
			$applicantExamTbl->deletePasswordByIdx($applicantInfo["idx"]);
			$this->mailByApplicantExam($applicantInfo,$sqlSet,$managerArray,$examRecordIdx);
			if($examRecordInfo['mail_delay'] == '0'){
			$this->mailByAdminToApplicant($applicantInfo,$examRecordRecent,$caseText,$majorText,$managerArray);
			}
			unset($session->id);			
			echo "
			<script>
			self.location.href='/applicant/examclear';
			</script>
			";	
		  }
			// 作成：丁錫圓
			// 修正：丁錫圓
			// 修正日：24/06/14 
			// 修正前：
			// if($submit_post=='cancel'){
			// 	if (isset($session->id)) {
			// 		$emailId = $session->id;
			// 		unset($emailId);
			// 		$applicantExamTbl->deletePasswordByIdx($applicantInfo["idx"]);
			// 		$applicantExamTbl->disqualificationByCancel($examRecordInfo['idx']);
			// 		session_unset(); 
			// 		$this->CancelToLogin();	
			// 	}
		  // }

			// and all session unset part
			// 修正後
		  if($submit_post=='cancel'){
				if (isset($session->id)) {
					unset($session->id);
					$applicantExamTbl->deletePasswordByIdx($applicantInfo["idx"]);
					$applicantExamTbl->disqualificationByCancel($examRecordInfo['idx']);
					$this->CancelToLogin();	
				}
		  }
			/* ここまで */
	  // Set variables to be passed to the layout
	  $viewModel = new ViewModel($datas);
  
	  // Set view template
	  $viewModel->setTemplate("applicant/exam.phtml");

	  return $viewModel;
  }

	/* log
	作成：丁錫圓
	作成日：24/06/07
	*/ 
	public function timeoutAction()
	{
		$post = $this->params()->fromPost();
		$examIdx = $post['idx']; 
		$applicantExamTbl = $this->getServiceLocator()->get("ApplicantExamTable");
		
		$session = new Container("applicant");
		// $emailId = $session->id;
		if (isset($session->id)) {
		  $emailId = $session->id;
		} else {
		$this->RedirectToLogin();		  
	  }
		$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");
		$applicantInfo    = $applicantExamTbl->readById($emailId);
		$examRecordInfo =  $applicantExamTbl->readByApplicantIdx($applicantInfo);
		$recordCode    = $examRecordInfo["diagnosis_code"];	
		$diagnosisInfo = $diagnosisTb->ReadForRecordByCodenDate($recordCode, $examRecordInfo["diagnosis_date"]);	
		$diagnosisTime = $diagnosisInfo['time_limit'];
		// 作成：丁錫圓
		// 修正：丁錫圓
		// 修正日：24/06/13
		// 修正前：
		// $timeout = $applicantExamTbl->timeout($examIdx, $diagnosisTime);

		// 	if ($timeout === 'timeout') {
		// 			// Handle timeout case
		// 			$applicantExamTbl->deletePasswordByIdx($applicantInfo['idx']);
		// 			session_unset();
		// 			echo json_encode(array('status' => 'timeout'));
		// 	} elseif (is_string($timeout)) {
		// 		echo $timeout; // Assuming $timeout is already JSON encoded by timeout() method
		// 	} else {
		// 			// Handle unexpected cases
		// 			http_response_code(500); // Internal Server Error
		// 			echo json_encode(array('error' => 'Unexpected error occurred'));
		// 	}
			
    // // Terminate script execution
    // exit;
		// 修正後：
		$timeout = $applicantExamTbl->timeout($examIdx, $diagnosisTime);
		error_log("Timeout response: " . $timeout);

		$timeoutData = json_decode($timeout, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			$jsonError = json_last_error_msg();
			http_response_code(500); 
			echo json_encode(array('error' => 'Invalid JSON response', 'message' => $jsonError, 'raw_response' => $timeout));
			exit;
		}
		if (isset($timeoutData['status']) && $timeoutData['status'] === 'timeout') {
				$applicantExamTbl->deletePasswordByIdx($applicantInfo['idx']);
				unset($session->id);
				echo $timeout; 
		} elseif (isset($timeoutData['status']) && $timeoutData['status'] === 'success') {
				echo $timeout; 
		} else {
				http_response_code(500); 
				echo json_encode(array('error' => 'Unexpected error occurred'));
		}
		exit;
	}
		// ここまで

function mailByApplicantExam($applicantInfo,$sqlSet,$managerArray,$examRecordIdx){
	$mail = new MailSender();

	// 기본 메일 전송 관련 설정 로드
	$param['config']=$this->getConfig();
	// 메일 제목 지정 (일반적으로 DB에 메일폼 테이블을 만들어서 그것을 가져와서 아래의 title contents에 넣지만, 이건 샘플이므로 간단히.)
	// 사람마다 변환해야 할 부분은 {{이렇게}} 메일폼에 넣어놓는다.
	$param['title']="ITスキル診断担当者様、{{name}}診断者の試験結果が出ました。";
	$param["content"] = "以下の診断者の試験結果をご参照ください。\n\n"
										. "お名前（漢字）：{{name}}\n"
										. "お名前（カナ）：{{kana}}\n"
										. "メールアドレス：{{email}}\n"
										. "得点：{{point}}\n"
										. "評価：{{rank}}\n"
										. "評価結果：{{comment}}\n\n"
										. "診断者ページ：{{url}}/situation/detail/{{idx}}";

	$param['title']=str_replace("{{name}}",$applicantInfo["name"],$param['title']);

	$param['content']=str_replace("{{name}}",$applicantInfo["name"],$param['content']);
	$param['content']=str_replace("{{kana}}",$applicantInfo["kana"],$param['content']);
	$param['content']=str_replace("{{email}}",$applicantInfo["email"],$param['content']);
	$param['content']=str_replace("{{point}}",$sqlSet["get_point"],$param['content']);
	$param['content']=str_replace("{{rank}}",$sqlSet["rank"],$param['content']);
	$param['content']=str_replace("{{comment}}",$sqlSet["diagnosis_comment"],$param['content']);
	$param['content']=str_replace("{{url}}",$param['config']['user-url']['admin'],$param['content']);
	$param['content']=str_replace("{{idx}}",$examRecordIdx,$param['content']);


	$param['email']=$managerArray[0];
	$param['password']=$managerArray[1];
	$param['name']=$managerArray[2];
	$param['smtp_password']=$managerArray[3];

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
	if ($status === 'FALSE') {
    echo "<script>alert('メールの送信に失敗しました。');</script>";
}
  }

  function mailByAdminToApplicant($applicantInfo,$examRecordRecent,$caseText,$majorText,$managerArray){
	$mail = new MailSender();

	// 기본 메일 전송 관련 설정 로드
	$param['config']=$this->getConfig();
	// 메일 제목 지정 (일반적으로 DB에 메일폼 테이블을 만들어서 그것을 가져와서 아래의 title contents에 넣지만, 이건 샘플이므로 간단히.)
	// 사람마다 변환해야 할 부분은 {{이렇게}} 메일폼에 넣어놓는다.
	$param['title']="ITスキル診断結果のお知らせ（ジエンジサービス）";

	$param["content"] = "{{applicant_name1}}様\n"
										. "お世話になっております。\n"
										. "株式会社ジエンジサービスITスキル診断担当です。\n\n"
										. "弊社のITスキル診断に受験いただき、誠にありがとうございました。\n"
										. "ITスキル診断結果が出ましたので、お知らせさせて頂きます。\n"
										. "診断内容についてご確認をお願いいたします。\n\n"
										. "＜申請者情報＞\n"
										. "申 請 者：{{applicant_name}}（{{kana}}）\n"
										. "応募区分：{{case}}\n"
										. "学　　歴：{{education}}\n"
										. "専　　攻：{{major}}\n"
										. "試 験 日：{{execute_date}}\n\n"
										. "＜診断結果＞\n"
										. "得     点：{{get_point}}\n"
										. "評     価：{{rank}}/（A~F）\n"
										. "評価結果：{{diagnosis_comment}}\n\n"
										. "※ITスキル診断に不明点などございましたら下記の宛先まで\n"
										. "   お問い合わせください。\n\n"
										. "＜問い合わせ先＞\n"
										. "担当者：ITスキル診断担当\n"
										. "連絡先：{{admin_id}}\n\n"
										. "以上、よろしくお願いいたします。\n"
										. "※このメールに返信しないでください。";
	$param['content']=str_replace("{{applicant_name1}}",$applicantInfo["name"],$param['content']);
	$param['content']=str_replace("{{applicant_name}}",$applicantInfo["name"],$param['content']);
	$param['content']=str_replace("{{kana}}",$applicantInfo['kana'],$param['content']);
	$param['content']=str_replace("{{case}}",$caseText,$param['content']);
	$param['content']=str_replace("{{education}}",$examRecordRecent["education"],$param['content']);
	$param['content']=str_replace("{{major}}",$majorText,$param['content']);
	$param['content']=str_replace("{{execute_date}}",$examRecordRecent["execute_date"],$param['content']);
	$param['content']=str_replace("{{get_point}}",$examRecordRecent["get_point"],$param['content']);
	$param['content']=str_replace("{{rank}}",$examRecordRecent["rank"],$param['content']);
	$param['content']=str_replace("{{diagnosis_comment}}",$examRecordRecent["diagnosis_comment"],$param['content']);
	$param['content']=str_replace("{{admin_id}}",$managerArray[0],$param['content']);

	// 수신자 이메일과 이름 설정
	$param['managerEmail']=$managerArray[0];
	$param['email']=$applicantInfo["email"];;
	$param['password']=$managerArray[1];
	$param['name']=$managerArray[2];
	$param['smtp_password']=$managerArray[3];

	// 전송
	$result = $mail->mailApplicant($param);
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
	if ($status === 'FALSE') {
    echo "<script>alert('メールの送信に失敗しました。');</script>";
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

	function examclearAction() {
		$this->layout("/applicant/examclear");
	}

	function CancelToLogin() {
		echo "
		<script>
			alert('ログアウトされました。');
			self.location.href='/applicant/login';
		</script>
		";
		exit;
	}

	function RedirectToLogin() {
		echo "
		<script>
			alert('ログインしてください');
			self.location.href='/applicant/login';
		</script>
		";
		exit;
	}

	function GetOptionDatasForInput() {
		$optionTb = $this->getServiceLocator()->get("ApplicationTable");
		$beforeOptionDatas = iterator_to_array($optionTb->ReadValid());

		$afterOptionDatas = array();
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

		$afterOptionDatas["level"] = array();

		return $afterOptionDatas;
	}	
}
