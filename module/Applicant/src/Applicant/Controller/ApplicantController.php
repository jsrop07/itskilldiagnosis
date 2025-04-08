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
	$session = new Container("applicant");
	//モデル連動
    $p = $this->params()->fromPost();
    if (isset($p["id"])) {
			$loginTbl = $this->getServiceLocator()->get("ApplicantLoginTable");
			$result = $loginTbl->login($p["id"], $p["password"]);
			$resultJson = json_decode($loginTbl->login($p["id"], $p["password"]));

			if ($resultJson->status == "success") {
				$session["userid"] = $p["id"];
			}
			die($result);
    }

		if(isset($session['userid']) && $session['userid'] != '') {
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
	  if (isset($session->userid)) {
		  $emailId = $session->userid;
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
			if ($get_point >= $selectedRank[0]['result_points']) {
				$recordRank="A";
				$recordExamResult=$selectedRank[0]['result_comments'];
			}
			elseif($get_point < $selectedRank[0]['result_points'] && $get_point >= $selectedRank[1]['result_points']){
				$recordRank="B";
				$recordExamResult=$selectedRank[1]['result_comments'];

			}
			elseif($get_point < $selectedRank[1]['result_points'] && $get_point >= $selectedRank[2]['result_points']){
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
		if ($submit_post == 'btn_submit') {
			$sqlWhere["idx"] = $examRecordInfo['idx'];
			$sqlSet["answer_data"] = $answer_data;
			$sqlSet["get_point"] = $percentPoint;
			$sqlSet['rank'] = $recordRank;
			$sqlSet['diagnosis_comment'] = $recordExamResult;
			$sqlSet['solve_time'] = $post['solveTime'];
		
			if ($examRecordInfo['mail_delay'] == '0') {
				$sqlSet["date_mail"] = date("Y-m-d H:i:s");
				$applicantExamTbl->updateExam($sqlWhere, $sqlSet);			
				$examRecordRecent =  $applicantExamTbl->readByApplicantIdx($applicantInfo);
				$applicantExamTbl->deletePasswordByIdx($applicantInfo["idx"]);
				$this->mailByAdminToApplicant($applicantInfo, $examRecordRecent, $caseText, $majorText, $managerArray);
				$this->mailByApplicantExam($applicantInfo,$sqlSet,$managerArray,$examRecordIdx);
			} else {
				$applicantExamTbl->updateExam($sqlWhere, $sqlSet);			
				$examRecordRecent =  $applicantExamTbl->readByApplicantIdx($applicantInfo);
				$applicantExamTbl->deletePasswordByIdx($applicantInfo["idx"]);
				$this->mailByApplicantExam($applicantInfo,$sqlSet,$managerArray,$examRecordIdx);
			}
		
			unset($session->userid);
		
			echo "
			<script>
					self.location.href='/applicant/examclear?examIdx=" . $datas['examIdx'] . "';
			</script>
			";
		}
		
			// 作成：丁錫圓
			// 修正：丁錫圓
			// 修正日：24/06/14 
			// 修正前：
			// if($submit_post=='cancel'){
			// 	if (isset($session->userid)) {
			// 		$emailId = $session->userid;
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
				if (isset($session->userid)) {
					unset($session->userid);
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
	public function  gettimeAction()
	{
		$post = $this->params()->fromPost();
		$examIdx = $post['idx']; 
		$applicantExamTbl = $this->getServiceLocator()->get("ApplicantExamTable");
		
		$session = new Container("applicant");
		// $emailId = $session->userid;
		if (isset($session->userid)) {
		  $emailId = $session->userid;
		} 
		$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");
		$applicantInfo    = $applicantExamTbl->readById($emailId);
		$examRecordInfo =  $applicantExamTbl->readByApplicantIdx($applicantInfo);
		$recordCode    = $examRecordInfo["diagnosis_code"];	
		$diagnosisInfo = $diagnosisTb->ReadForRecordByCodenDate($recordCode, $examRecordInfo["diagnosis_date"]);	
		$diagnosisTime = $diagnosisInfo['time_limit'];

		$timeout = $applicantExamTbl->timeout($examIdx, $diagnosisTime);
		error_log("Timeout response: " . $timeout);

		$timeoutData = json_decode($timeout, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			$jsonError = json_last_error_msg();
			http_response_code(500); 
			echo json_encode(array('error' => 'Invalid JSON response', 'message' => $jsonError, 'raw_response' => $timeout));
			exit;
		}
		// if (isset($timeoutData['status']) && $timeoutData['status'] === 'timeout') {
		// 		$applicantExamTbl->deletePasswordByIdx($applicantInfo['idx']);
		// 		// unset($session->userid);
		// 		echo $timeout; 
		// } else
		if (isset($timeoutData['status']) && $timeoutData['status'] === 'success') {
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


	$protocol = $_SERVER['SERVER_PORT'] === 443 ? 'https://' : 'http://';
	$pdfUrl = $protocol . $_SERVER['SERVER_NAME'];

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
										. "診断結果分析表：{{diagnosis}}/pdf_diagnosis.html?id=" . $examRecordRecent['idx'] . "\n"
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
	$param["content"]=str_replace("{{diagnosis}}", $pdfUrl, $param["content"]);

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

	function examclearAction() {
		$p = $this->params()->fromPost();
		$q = $this->params()->fromQuery();
				
		$this->layout("/applicant/examclear");
	}

	public function itdiagnosisAction() {
		$p = $this->params()->fromPost();
		$q = $this->params()->fromQuery();
		$recordTb = $this->getServiceLocator()->get("RecordTable-Admin");
		$recordData = "";
		try {
			$recordData = $recordTb->ReadByIdx($p);
		} catch (\Exception $e) {
			$logData["reason"] = "exception at SituationController detailAction RecordTable ReadByIdx";
			$logData["message"] = $e->getMessage();
			$log = $LogModule->SaveLog($logData);
			die($log);
		}
	
		if (is_null($recordData["diagnosis_date"])) {
			header("Location: ../edit/" . $idx);
			exit;
		}
		$datas["recordData"] = $recordData;
		$datas['optionDatas'] = $this->GetOptionDatas($datas);
	
		$applicantTb = $this->getServiceLocator()->get("ApplicantTable-Admin");
		try {
			$datas["applicantData"] = $applicantTb->ReadByIdx($recordData["applicant_idx"]);
		} catch (\Exception $e) {
			$logData["reason"] = "exception at SituationController detailAction ApplicantTable ReadByIdx";
			$logData["message"] = $e->getMessage();
			$log = $LogModule->SaveLog($logData);
			die($log);
		}

	
		$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");
		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		$optionTb = $this->getServiceLocator()->get("OptionTable");

		$diagnosisData = $diagnosisTb->ReadByCode($recordData["diagnosis_code"]);
		$answerDatas = explode(",", $recordData["answer_data"]);
		$questionIdxDatas = explode(",", $diagnosisData["question_idxs"]);

		$tableDatas = array();
		for ($i = 0; $i < count($questionIdxDatas); $i++) {
			$questionData = $questionTb->ReadByIdx($questionIdxDatas[$i]);

			$correctChar = "X";
			if ($questionData["correct"] == $answerDatas[$i]) { $correctChar = "O"; }

			$tableData["no"] = $i + 1;
			$tableData["title"] = $questionData["title"];
			$tableData["level"] = $questionData["level"];
			$tableData["point"] = $questionData["point"];


			$tableData["answerDatas"] = $answerDatas[$i];
			
			
			$tableData["correct"] = $questionData["correct"];
			$tableData["point"] = $questionData["point"];
			$tableData["class1st"] = $optionTb->ReadByIdx($questionData["class1st"])["text"];
			$tableData["class2nd"] = $optionTb->ReadByIdx($questionData["class2nd"])["text"];
			$tableData["correctChar"] = $correctChar;

			$tableDatas[] = $tableData;
		}

		$datas["tableDatas"] = $tableDatas;
		$count = [];
		
	// 분류별 집계
	foreach ($tableDatas as $data) {
		$class2nd = $data["class2nd"];
		$title = $data["title"];

		// 분류 기준
		if ($class2nd === "論理的思考") {
			$key = "論理的思考";
		} elseif (strpos($title, "algorithm") !== false) {
			$key = "algorithm";
		} else {
			$key = $class2nd;
		}
		// 초기화
		if (!isset($count[$key])) {
			$count[$key] = [
				"total" => 0,
				"correct" => 0,
				"point_total" => 0,
				"point_correct" => 0
			];
		}

		// 全体問題数
		$count[$key]["total"]++;

		// ポイント累積
		$point = $data["point"] ?? 0;
		$count[$key]["point_total"] += $point;

		// 正答処理
		if (isset($data["answerDatas"], $data["correct"]) && $data["answerDatas"] == $data["correct"]) {
			$count[$key]["correct"]++;
			$count[$key]["point_correct"] += $point;
		}
	}

	// $values = array_values(array_slice($count, 0, 3));
	// $percentPoints = []; 
	// $pointCorrects = [];
	
	// foreach ($values as $i => $data) {
	// 	$percentPoint = ceil($data["point_total"] / $diagnosisData['point_total'] * 100);
	// 	$point_correct = ceil($data["point_correct"] / $diagnosisData['point_total'] * 100);
	
	// 	$percentPoints[] = $percentPoint;
	// 	$pointCorrects[] = $point_correct;
	// }



	// 250327診断分析グラフ
	
		$datass = [];

		foreach ($count as $key => $data) {
			$datass[] = [
				'label' => $key,
				'correct' => $data["point_correct"],
				'total' => $data["point_total"]
			];
		}

		$width = 800;
		$height = 800;

		$image = imagecreatetruecolor($width, $height);
		if (!function_exists('imagecreatetruecolor')) {
			die('GD 라이브러리가 설치되어 있지 않습니다.');
		}
		imagesavealpha($image, true);
		$bg_color = imagecolorallocatealpha($image, 255, 255, 255, 0);
		imagefill($image, 0, 0, $bg_color);
		
		$line_color = imagecolorallocate($image, 0, 0, 255);
		$gray_color = imagecolorallocate($image, 220, 220, 220);
		$text_color = imagecolorallocate($image, 0, 0, 0);
		$fill_color = imagecolorallocatealpha($image, 144, 238, 144, 80); 
		
		$centerX = $width / 2;
		$centerY = $height / 2;
		$radius = 300;
		$angle = 360 / count($datass);

		
		$fontPath = dirname(__DIR__, 5)  . '/vendor/dompdf/dompdf/lib/fonts/ipaexm.ttf'; // TTF 경로
		// ▶ 원형 보조선 + 수치
		for ($i = 1; $i <= 4; $i++) {
			$r = $radius * $i / 4;
			imageellipse($image, $centerX, $centerY, $r * 2, $r * 2, $gray_color);
			$value = 	$diagnosisData['point_total'] * $i / 4;
			// imagettftext($image, 20, 0, $centerX + 10, $centerY - $r + 10, $text_color, $fontPath, (string)$value);
		}
		
		// ▶ 축선 + 라벨
		foreach ($datass as $index => $data) {
			$currentAngle = deg2rad($index * $angle - 90);
			$x = $centerX + cos($currentAngle) * $radius;
			$y = $centerY + sin($currentAngle) * $radius;
			imageline($image, $centerX, $centerY, $x, $y, $gray_color);
		
			$labelX = $centerX + cos($currentAngle) * ($radius + 30);
			$labelY = $centerY + sin($currentAngle) * ($radius + 30);
			imagettftext($image, 28, 0, $labelX - 20, $labelY, $text_color, $fontPath, ucfirst($data['label']));
		}
		
		// ▶ 데이터 점 좌표 계산
		$points = [];
		foreach ($datass as $index => $data) {
			$currentAngle = deg2rad($index * $angle - 90);
			
			// 비율: 자기 자신의 총점 기준
			$rate = $data['correct'] / $data['total'];
			$x = $centerX + cos($currentAngle) * ($radius * $rate);
			$y = $centerY + sin($currentAngle) * ($radius * $rate);
			
			$points[] = $x;
			$points[] = $y;
		}
		
		
		// ▶ 내부 면 채우기 + 외곽선
		imagefilledpolygon($image, $points, count($datass), $fill_color);
		imagepolygon($image, $points, count($datass), $line_color);
		
		// ▶ 저장
		$chartImagePath = $_SERVER['DOCUMENT_ROOT'] . "/img/radar_chart.png";
		imagepng($image, $chartImagePath);
		imagedestroy($image);



	// 예시 데이터
	$barData = [];
	foreach ($count as $key => $data) {
			$barData[] = [$key, $data["point_correct"], $data["point_total"]];
	}

	// 크기 설정
	$canvasW = 900; // 넉넉하게
	$canvasH = 600;

	$chartImg = imagecreatetruecolor($canvasW, $canvasH);
	imagesavealpha($chartImg, true);
	$bgAlpha = imagecolorallocatealpha($chartImg, 255, 255, 255, 0);
	imagefill($chartImg, 0, 0, $bgAlpha);

	// 색상
	$correctColor = imagecolorallocate($chartImg, 144, 238, 144); // #90EE90
	$wrongColor = imagecolorallocate($chartImg, 255, 153, 153);   // #FF9999
	$fontColor = imagecolorallocate($chartImg, 0, 0, 0);

	$jpFont = dirname(__DIR__, 5)  . '/vendor/dompdf/dompdf/lib/fonts/ipaexm.ttf';

	$leftPad = 200;
	$topPad = 130;
	$rightPad = 60;
	$bottomPad = 50;
	$barHeight = 40;
	$barGap = 40;

	$totalBars = count($barData);
	$chartAreaW = $canvasW - $leftPad - $rightPad;
	$startY = $topPad;
	$percent = [];
	foreach ($barData as $idx => $item) {
			list($label, $correct, $total) = $item;
			$percent = $correct / $total;
			$correctLength = $chartAreaW * $percent;
			$wrongLength = $chartAreaW * (1 - $percent);

			$topY = $startY + ($barHeight + $barGap) * $idx;

			// ▶ 정답 부분
			imagefilledrectangle($chartImg, $leftPad, $topY, $leftPad + $correctLength, $topY + $barHeight, $correctColor);

			// ▶ 오답 부분
			imagefilledrectangle($chartImg, $leftPad + $correctLength, $topY, $leftPad + $correctLength + $wrongLength, $topY + $barHeight, $wrongColor);

			// ▶ 항목 라벨
			imagettftext($chartImg, 28, 0, 10, $topY + $barHeight - 10, $fontColor, $jpFont, ucfirst($label));

			// ▶ 퍼센트 텍스트
			$percentText = ceil($percent * 100) . '%';
			$textX = $leftPad + $correctLength + 10;

			// 텍스트가 캔버스 오른쪽 끝을 넘어가면 안쪽으로
			if ($textX + 50 > $canvasW - $rightPad) {
					$textX = $leftPad + $correctLength - 45;
			}

			imagettftext($chartImg, 20, 0, $textX, $topY + $barHeight - 10, $fontColor, $jpFont, $correct);
	}
	// print_r($barData);exit;

	$barChartPath = $_SERVER['DOCUMENT_ROOT'] . "/img/bar_chart_horizontal_stacked_percent.png";
	imagepng($chartImg, $barChartPath);
	imagedestroy($chartImg);

// ここまで


		// PDF에 차트 이미지 삽입
		$recordClass2nd = $datas['optionDatas']['recordData']['class2nd'];
		$optionDatasClass2nd = $datas['optionDatas']['optionDatas'][$recordClass2nd];
		$skillTexts = ["有", "無"];
		$genderTexts = ["男", "女"];
		$recordDataSkill = $datas["recordData"]['skill'];
		$recordDataGender = $datas["applicantData"]['gender'];
		$logoPath = $_SERVER['DOCUMENT_ROOT'] . "/img/logo_about.png";
		$result = [];   
		$htmlTemplatePath = $_SERVER['DOCUMENT_ROOT'] . "/pdf/diagnosis_sheet.html";
	
		$dir_route = $_SERVER['DOCUMENT_ROOT'] . "/pdf/";
		$filename = "IT診断分析表" .  ".pdf";
	
		$html = file_get_contents($htmlTemplatePath);
		$html = str_replace("{{logo}}", $logoPath, $html);
		$html = str_replace("{{name}}", $datas["applicantData"]['name'], $html);
		$html = str_replace("{{gender}}", $genderTexts[$recordDataGender], $html);
		$html = str_replace("{{education}}", $datas["recordData"]['education'], $html);
		$html = str_replace("{{get_point}}", $datas["recordData"]['get_point'], $html);
		$html = str_replace("{{solve_time}}", $datas["recordData"]['solve_time'], $html);
		$html = str_replace("{{rank}}", $datas["recordData"]['rank'], $html);
		$html = str_replace("{{class2nd}}", $optionDatasClass2nd, $html);
		$html = str_replace("{{skill}}", $skillTexts[$recordDataSkill], $html);
		$html = str_replace("{{apply_date}}", date("Y-m-d", strtotime($datas["applicantData"]['apply_date'])), $html);
		$html = str_replace("{{diagnosis_comment}}", $datas["recordData"]['diagnosis_comment'], $html);
		$html = str_replace("{{chartData}}", $chartImagePath, $html);
		$html = str_replace("{{chartData2}}", $barChartPath, $html);
		
        $explanations = [
            // 配列1：論理問題
            "logic" => [
                "不十分" => "論理的思考力が不足しており、問題解決に困難を感じています。 基本的な推論問題を繰り返し解くことをおすすめします。",
                "普通" => "基本的な論理的思考は可能ですが、複雑な問題ではやや混乱する傾向があります。 さまざまな問題形式に触れてみましょう。",
                "優秀" => "論理的思考力が優れており、ほとんどの問題を的確に解決できています。 より難易度の高い問題にも挑戦してみましょう。",
                "卓越" => "非常に優れた論理的思考力を持ち、問題解決能力が卓越しています。さらに深い思考や多様な問題に取り組むことで、 より高い成長が期待できます。"
            ],
            // 配列2：コーディング言語の基礎
            "basic" => [
                "不十分" => "プログラミング言語に関する理解が不足しています。変数、条件分岐、ループなど、 基本的な文法から再学習することをおすすめします。",
                "普通" => "基本的な文法はある程度理解していますが、ミスが多く見られます。 短いコードから実際に書いてみて、慣れていきましょう。",
                "優秀" => "基礎文法をしっかり理解しており、実際のコード記述にも慣れています。 さまざまな言語でも練習してみましょう。",
                "卓越" => "プログラミング言語の基礎を完璧に理解しており、 実際の問題解決にも自然に応用できています。"
            ],
            // 配列3：アルゴリズム（応用）
            "advanced" => [
                "不十分" => "アルゴリズムの理解度が低く、問題解決に困難を感じています。 基本的なアルゴリズムから少しずつ学習を進めましょう。",
                "普通" => "基本的なアルゴリズムは理解していますが、複雑な問題の解決には時間がかかります。 アルゴリズム問題の演習を増やしましょう。",
                "優秀" => "アルゴリズムへの理解が深く、多様な問題にも柔軟に対応できています。 さらに多角的なアプローチを試してみてください。",
                "卓越" => "複雑なアルゴリズムの問題にも迅速かつ正確に対応できます。 最適化や計算量の改善にも挑戦してみましょう。"
            ]
        ];
        

		$values = array_values(array_slice($count, 0, 3));
		foreach ($values as $i => $data) {
			// $percentPoint = ceil($data["point_total"]/$diagnosisData['point_total']*100);
			// $point_correct = ceil($data["point_correct"]/$diagnosisData['point_total']*100);

			$html = str_replace("{{count" . ($i + 1) . "}}", $data["total"], $html);
			$html = str_replace("{{count" . ($i + 4) . "}}", $data["correct"], $html);
			$html = str_replace("{{count" . ($i + 7) . "}}", $data["point_total"], $html);
			$html = str_replace("{{count" . ($i + 10) . "}}", $data["point_correct"], $html);


            if($data['point_total'] == 0){
                $grade = "評価不可";
            }else{
                $rate = $data['point_correct'] / $data['point_total'];
                if($rate <0.4){
                    $grade = "不十分";
                }elseif ($rate >= 0.4 && $rate < 0.6) {
                    $grade = "普通";
                } elseif ($rate >= 0.6 && $rate < 0.8) {
                    $grade = "優秀";
                } else {
                    $grade = "卓越";
                }
            }
                // 분야별 키 설정
            if ($i == 0) $key = "logic";
            elseif ($i == 1) $key = "basic";
            else $key = "advanced";

            // 해설 가져오기
            $comment = $explanations[$key][$grade];

            $html = str_replace("{{comment" . ($i + 1) . "}}", $comment, $html);
		}

		$options = new Options();
		$dompdf = new Dompdf();
		$dompdf->set_option("paperSize", "a4");
		$dompdf->set_option('defaultMediaType', 'all');
		$dompdf->set_option('isFontSubsettingEnabled', true);
		$dompdf->setPaper('a4', 'portrait');
		$dompdf->loadHtml($html, 'UTF-8');
		$dompdf->render();
		$contents_data = $dompdf->output();
	
		file_put_contents($dir_route . $filename, $contents_data);
	
		$pdf_url = "/pdf/" . $filename;
	
		$result[] = [
			"pdf_url" => $pdf_url
		];
		ini_set('display_errors', 1);
		error_reporting(E_ALL);
		header('Pragma: public');
		header('Expires: 0');
		header('Content-Type: application/pdf');
		header('Content-Description: File Transfer');
		header("Content-Disposition: inline; filename*=UTF-8''" . rawurlencode($pdf_url));
		header('Content-Transfer-Encoding: binary');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Length: ' . strlen($contents_data));
		ob_clean();
		flush();
		echo $contents_data;
	
		echo json_encode($result);
		exit;
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
