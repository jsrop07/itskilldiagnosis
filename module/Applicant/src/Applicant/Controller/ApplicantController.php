<?php

namespace Applicant\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\Controller\Plugin\Redirect;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Session\Container;

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
	// print_r($p);
	$viewModel = new ViewModel(['questionType' => $questionType,'develop' => $develop,'p' => $p]);
	$viewModel->setTemplate("/applicant/application.phtml");

	if ($mode == 'btn_submit') {
		// exit;
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
				$session["token"] = 1;
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
	  $p = $this->params()->fromPost();
	  $mode =(isset($p['mode'])    &&   $p['mode'] !='')? $p['mode']:'';


	  $session = new Container("applicant");
	  if (isset($session->id)) {
		  $id = $session->id;
		} else {
		$this->RedirectToLogin();		  
	  }

	  if($mode=='cancel'){
		if (isset($session->id)) {
            $id = $session->id;
            unset($id);
            session_unset(); 
			$this->RedirectToLogin();	
        }
	  }
	  if($mode=='submit'){
		echo "
		<script>
		self.location.href='/applicant/examclear';
		</script>
		";	
	  }
	  // Get exam name
	  $examTb = $this->getServiceLocator()->get("ApplicantExamTable");
	  $examData = $examTb->readByUrl($id);
	  $name = $examData["name"];
	  
	  //TIMELIMIT TEST
	  $idx = $examData["idx"];
  
	  // Set variables to be passed to the layout
	  $viewModel = new ViewModel(["name" => $name, "idx" => $idx]);
  
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

	    /** Make ViewModel with datas and template */
	function SetViewModel($datas, $template) {
		$this->layout("layout/admin/layout_default");
		$vm = new ViewModel($datas);
		$vm->setTemplate($template);
		return $vm;
	}

	
	/** Read examTable for Testing page (user/exam.phtml) */
	public function Setting($examData) {		// 問題設定ページに移動
		$datas["examData"] = $examData;

		$typeTb = $this->getServiceLocator()->get("QuestionTypeTable");
		$datas["typeDatas"] = $typeTb->readAll();
		
		return $this->SetViewModel($datas, "user/setting.phtml");
	}

	/** Save question_data in examTable */
	public function MakeExam($url, $question_nums, $post) {		// 試験の問題を登録
		if (isset($post["major"])) { $post["academic"] += 1; }

		$questionTb = $this->getServiceLocator()->get("QuestionPoolTable");
		$examTb = $this->getServiceLocator()->get("ExamTable");
		
		$post["num"] = $question_nums;
		$questionDatas = iterator_to_array($questionTb->ReadRandForExam($post));
		
		$question_data = "";
		foreach ($questionDatas as $data) {		// 選択した問題のidxを保存
			$question_data .= $data["idx"] . ",";
		}
		$post["question_data"] = substr($question_data , 0, -1);

		$examTb->updateExamSetting($url, $post);
	}
}
