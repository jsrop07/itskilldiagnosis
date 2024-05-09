<?php

namespace Applicant\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\Controller\Plugin\Redirect;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Session\Container;
use Zend\Crypt\Password\Bcrypt;


class ApplicantController extends AbstractActionController
{
  public function applicationAction()
  {
	$this->layout("layout/applicant/application_layout");
	$config=$this->getServiceLocator()->get('config');
	//モデル連動
	$tbl=$this->getServiceLocator()->get('ApplicationTable');
	$questionType=$tbl->getQuestionType();
	$develop=$tbl->getDevelop();
	$p = $this->params()->fromPost();
	$mode =(isset($p['mode'])    &&   $p['mode'] !='')? $p['mode']:'';
	$viewModel = new ViewModel(['questionType' => $questionType,'develop' => $develop,'p' => $p]);
	$viewModel->setTemplate("/applicant/application.phtml");

	if ($mode == 'btn_submit') {
		$email = $this->params()->fromPost('email');
		$name = $this->params()->fromPost('name');
		$kana = $this->params()->fromPost('kana');
		$gender = $this->params()->fromPost('gender');
		$birth = $this->params()->fromPost('birth');
		$application_category = $this->params()->fromPost('application_category');
		$education = $this->params()->fromPost('education');
		$major = $this->params()->fromPost('major');
		$skill = $this->params()->fromPost('skill');
		$question_type = $this->params()->fromPost('question_type');
		$develop = $this->params()->fromPost('develop');
		$career = $this->params()->fromPost('career');
		$certificates = $this->params()->fromPost('certificates');
		$other = $this->params()->fromPost('other');
		$arr = [
			'email' => $email,
			'name' => $name,
			'kana' => $kana,
			'gender' => $gender,
			'birth' => $birth,
			'application_category' => $application_category,
			'education' => $education,
			'major' => $major,
			'skill' => $skill,
			'question_type' => $question_type,
			'develop' => $develop,
			'career' => $career,
			'certificates' => $certificates,
			'other' => $other,
		];
	   $tbl->insertAndUpdateApplication($arr);
	   echo "
	   <script>
	   self.location.href='/applicant/applicationclear';
	   </script>
	   ";

		exit;
	}

	return $viewModel;
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
	  $submit_post        = (isset($post['submit_post'])         &&   $post['submit_post'] !='')         ? $post['submit_post']         : '';
	  $answer_data = (isset($post['answer_data'])  &&   $post['answer_data'] !='')  ? $post['answer_data']  : '';
	  $comment     = (isset($post['comment'])      &&   $post['comment'] !='')      ? $post['comment']      : '';


	  $session = new Container("applicant");
	  if (isset($session->id)) {
		  $emailId = $session->id;
		} else {
		$this->RedirectToLogin();		  
	  }

	  if($submit_post=='cancel'){
		if (isset($session->id)) {
            $emailId = $session->id;
            unset($emailId);
            session_unset(); 
			$this->RedirectToLogin();	
        }
	  }

	  // 아이디 값 불러오기
	  $applicantExamTbl = $this->getServiceLocator()->get("ApplicantExamTable");
	  $applicantInfo    = $applicantExamTbl->readById($emailId);
	  $applicantIdx     = $applicantInfo["idx"];	  

	  // applicant의 id값과 record의 idx값 비교해서 불러오기
	  $examRecordInfo =  $applicantExamTbl->readByApplicantIdx($applicantInfo);
	  $datas["name"]  =  $applicantInfo["name"];
	  
	  
	  $applicantIdx   =  $examRecordInfo["applicant_idx"];
	  
	  // code diagnosis테이블의 code와 question_num, time_limit값 불러오기
	  $recordCode    = $examRecordInfo["code"];
	  $diagnosisInfo = $applicantExamTbl->readByDiagnosisCode($recordCode);

	  $datas["question_num"] = $diagnosisInfo["question_num"];
	  $datas["time_limit"]   = $diagnosisInfo["time_limit"];

	  // 정답값 비교하기
	  $findQuestionData      = $applicantExamTbl->findCompareIdx($post);
	  $selectedQuestion_idxs = ['question_idxs'=>isset($diagnosisInfo['question_idxs'])? $diagnosisInfo['question_idxs']:null];

	  $selectedQnA=[['question_idxs'=> $diagnosisInfo['question_idxs'],'answer_data'=>$examRecordInfo['answer_data']]];
	//   print_r($selectedQnA);

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
	  $datas["matchedData"]=$matchedData;


	  $output = [];
		foreach ($matchedData as $item) {
			$output[] = $item['correct'];
		}
		$result = implode(',', $output);
		$answerDataArray = explode(',',$answer_data);
		$resultArray = explode(',', $result);

		$length = count($answerDataArray);
		$get_point = 0;
		for ($i = 0; $i < $length; $i++) {
			if ($resultArray[$i] == $answerDataArray[$i]) {
				$get_point++;
			}
		}

		//제출하기
		if($submit_post=='btn_submit'){

			$sqlWhere["idx"] = $examRecordInfo['idx'];
			$sqlSet["answer_data"] = $answer_data;
			$sqlSet["get_point"] = $get_point;
			$sqlSet["comment"] = $comment;

			$applicantExamTbl->updateExam($sqlWhere, $sqlSet);			
			$applicantExamTbl->deletePasswordByIdx($applicantInfo["idx"]);
			session_unset(); 
			echo "
			<script>
			self.location.href='/applicant/examclear';
			</script>
			";	
		  }
	  // Set variables to be passed to the layout
	  $viewModel = new ViewModel($datas);
  
	  // Set view template
	  $viewModel->setTemplate("applicant/exam.phtml");

	  return $viewModel;
  }
  
  function applicationclearAction() {
	$this->layout("/applicant/applicationclear");
	// $this->layout("layout/applicant/application_layout");
	// $vm = new ViewModel();
    // $vm->setTemplate("/applicant/applicationclear.phtml");
    // return $vm;
	}

	function examclearAction() {
		$this->layout("/applicant/examclear");
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

	function listAction() {
		$this->layout("layout/admin/layout_default");
		$datas["breadcrumbData"] = ["ITスキル診断状況管理"];

		$query = $this->params()->fromQuery();
		unset($query["page"]);

		$page = $this->params()->fromQuery("page", 1);
		$printDataNum = 10;
		$questionTb = $this->getServiceLocator()->get("AppQuestionTable");
		$totalQuestionDatas = iterator_to_array($questionTb->ReadAllList());
		$paginationData = $questionTb->GetAllList();

		$datas["totalData"] = count($totalQuestionDatas);

		$vm = $this->SetViewModel($datas, "/admin/diagnosis_list.phtml");

		$vm->noticelist = $paginationData;
		$vm->noticelist->setCurrentPageNumber($page);
		$vm->noticelist->setItemCountPerPage($printDataNum);

		return $vm;
		
	}
	

	function inputAction(){
		$this->layout("layout/admin/layout_default");
		$datas["breadcrumbData"] = ["ITスキル診断状況管理"];

		$query = $this->params()->fromQuery();
		unset($query["page"]);


		$applicantTb = $this->getServiceLocator()->get("AppQuestionTable");
		// $applicantData = $applicantTb->readByUrl($id);

		// $name = $applicantData["name"];

		$vm = $this->SetViewModel($datas, "/admin/input.phtml");

		return $vm;
	}

	function inputconfirmAction(){
		$this->layout("layout/admin/layout_default");
		$datas["breadcrumbData"] = ["ITスキル診断状況管理"];

		$query = $this->params()->fromQuery();
		unset($query["page"]);


		$applicantTb = $this->getServiceLocator()->get("AppQuestionTable");
		// $applicantData = $applicantTb->readByUrl($id);

		// $name = $applicantData["name"];

		$vm = $this->SetViewModel($datas, "/admin/inputconfirm.phtml");

		return $vm;
	}

	function detailAction(){
		$this->layout("layout/admin/layout_default");
		$datas["breadcrumbData"] = ["ITスキル診断状況管理"];

		$query = $this->params()->fromQuery();
		unset($query["page"]);


		$applicantTb = $this->getServiceLocator()->get("AppQuestionTable");
		// $applicantData = $applicantTb->readByUrl($id);

		// $name = $applicantData["name"];

		$vm = $this->SetViewModel($datas, "/admin/detail.phtml");

		return $vm;
	}

	function detaileditAction(){
		$this->layout("layout/admin/layout_default");
		$datas["breadcrumbData"] = ["ITスキル診断状況管理"];

		$query = $this->params()->fromQuery();
		unset($query["page"]);


		$applicantTb = $this->getServiceLocator()->get("AppQuestionTable");
		// $applicantData = $applicantTb->readByUrl($id);

		// $name = $applicantData["name"];

		$vm = $this->SetViewModel($datas, "/admin/detailedit.phtml");

		return $vm;
	}

	function editconfirmAction(){
		$this->layout("layout/admin/layout_default");
		$datas["breadcrumbData"] = ["ITスキル診断状況管理"];

		$query = $this->params()->fromQuery();
		unset($query["page"]);


		$applicantTb = $this->getServiceLocator()->get("AppQuestionTable");
		// $applicantData = $applicantTb->readByUrl($id);

		// $name = $applicantData["name"];

		$vm = $this->SetViewModel($datas, "/admin/editconfirm.phtml");

		return $vm;
	}

	    /** Make ViewModel with datas and template */
	// function SetViewModel($datas, $template) {
	// 	$this->layout("layout/admin/layout_default");
	// 	$vm = new ViewModel($datas);
	// 	$vm->setTemplate($template);
	// 	return $vm;
	// }

		
}
