<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class QuestionController extends AbstractActionController
{
	public function indexAction() { print_r("Question Index"); exit; }
	
	public function listAction() {
		$datas["breadcrumbData"] = ["ITスキル診断問項管理"];
		$query = $this->params()->fromQuery();
		$page = 1;
		if (isset($query["page"])) {
			$page = $query["page"];
			unset($query["page"]);
		}

		$printDataNum = 10;

		$session = new Container("user");
		$userCode = $session["code"];
		$userLevel = $session["level"];

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		$totalQuestionDatas = array();
		if (!empty($query)) {
			foreach ($query as $key => $value) {
				$datas["inputDatas"][$key] = $value;
			}

			if (is_numeric(substr($query["register"], 0, 2)) && is_numeric(substr($query["register"], 2, 7))) {
				$query["admin_approve"] = $query["register"];
			}
			else {
				$adminTb = $this->getServiceLocator()->get("AdminTable");
				$query["admin_approve"] = $adminTb->ReadByName($query["register"])["code"];
			}

			unset($query["register"]);

			if ($userLevel >= 1) {
				$totalQuestionDatas = iterator_to_array($questionTb->ReadListByOption($query));
				$paginationData = $questionTb->GetListByOption($query);
			}
			else { $totalQuestionDatas = iterator_to_array($questionTb->ReadListByCode_Option($userCode, $query)); }
		}
		else {
			if ($userLevel >= 1) {
				$totalQuestionDatas = iterator_to_array($questionTb->ReadAllList());
				$paginationData = $questionTb->GetAllList();
			}
			else {
				$totalQuestionDatas = iterator_to_array($questionTb->ReadListByRegist($userCode));
				$paginationData = $questionTb->GetAllList($userCode);
			}
		}
		
		$totalData = count($totalQuestionDatas);
		$datas["totalData"] = $totalData;


		if (!empty($totalQuestionDatas)) {
			$PrintQuestionDatas = array();
			$startIdx = ($page - 1) * $printDataNum;
			$endIdx = ($page * $printDataNum);
			
			for ($i = 0; $startIdx + $i < $endIdx; $i++) {
				if (!isset($totalQuestionDatas[$startIdx + $i])) break;

				$PrintQuestionDatas[$i] = $totalQuestionDatas[$startIdx + $i];
				$PrintQuestionDatas[$i]["num"] = count($totalQuestionDatas) - ($startIdx + $i);
			}
			
			$datas["questionDatas"] = $PrintQuestionDatas;
		}
		$totalPage = ceil($totalData / $printDataNum);
		if ($totalPage < 2) { $totalPage = 0; }

		$datas = $this->GetOptionDatas($datas);

		$this->layout("layout/list");
		$vm = $this->SetViewModel($datas, "/question/question_list.phtml");
		$vm->noticelist = $paginationData;
		$vm->noticelist->setCurrentPageNumber($page);
		$vm->noticelist->setItemCountPerPage($printDataNum);
		return $vm;
	}
	
	public function registAction() {
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "問題登録"];
		$datas["title"] = "問題登録";

		$adminTb = $this->getServiceLocator()->get("AdminTable");
		$datas["adminDatas"] = iterator_to_array($adminTb->ReadAll());

		$datas = $this->GetOptionDatasForInput($datas);

		$this->layout("layout/list");
		return $this->SetViewModel($datas, "/question/question_input.phtml");
	}

	public function confirmAction() {
		$datas["title"] = "登録確認";
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "問題登録", "登録確認"];

		$post = $this->params()->fromPost();

		$answers = array();
		foreach ($post as $key => $value) {
			if (strpos($key, "answer") !== false) {
				array_push($answers, $value);
				unset($post[$key]);
			}
		}
		$post["answers"] = $answers;

		$datas["inputDatas"] = $post;
		$datas["printDatas"] = $post;

		$datas["inputDatas"]["answers"] = implode(",", $answers);

		$adminTb = $this->getServiceLocator()->get("AdminTable");
		$datas["printDatas"]["register"] = $adminTb->ReadByCode($post["admin_regist"])["name"];
			
		$optionTb = $this->getServiceLocator()->get("OptionTable");
		$optionDatas = iterator_to_array($optionTb->ReadAll());

		foreach ($optionDatas as $data) {
			$datas["optionDatas"][$data["idx"]] = $data["text"];
		}

		$this->layout("layout/list");
		return $this->SetViewModel($datas, "/question/question_confirm.phtml");
	}
	
	public function detailAction() {
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "問題詳細"];
		$route = $this->params()->fromRoute();
		$index = $route["index"];
		
		$questionTable = $this->getServiceLocator()->get("QuestionTable");
		$questionData = iterator_to_array($questionTable->ReadByIndex($index));
		$datas["questionData"] = $questionData;
		
		$adminTb = $this->getServiceLocator()->get("AdminTable");
		$datas["register"] = $adminTb->ReadByCode($questionData["admin_regist"])["name"];

		if ($questionData["date_approve"] != null) {
			$datas["approver"] = $adminTb->ReadByCode($questionData["admin_approve"])["name"];
		}


		$datas = $this->GetOptionDatas($datas);

		$this->layout("layout/list");
		return $this->SetViewModel($datas, "/question/question_detail.phtml");
	}

	public function editAction() {
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "問題詳細", "問題修正"];
		$datas["title"] = "問題修正";

		$idx =$this->params()->fromRoute("index");
		print_r($idx);

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		$datas["questionData"] = $questionTb->ReadByIndex($idx);

		$adminTb = $this->getServiceLocator()->get("AdminTable");
		$datas["adminDatas"] = iterator_to_array($adminTb->ReadAll());
		$datas["approvalDatas"] = iterator_to_array($adminTb->ReadApprovers());

		$datas = $this->GetOptionDatasForInput($datas);
		$this->layout("layout/list");
		return $this->SetViewModel($datas, "/question/question_input.phtml");
	}

	public function csvAction() {
		$this->layout("layout/list");
		return $this->SetViewModel([], "/question/question_csv.phtml");
	}

	/** Make ViewModel with datas and template */
	function SetViewModel($datas, $template) {
		$vm = new ViewModel($datas);
		$vm->setTemplate($template);
		return $vm;
	}

	function GetOptionDatas($datas) {
		$optionTb = $this->getServiceLocator()->get("OptionTable");
		$optionDatas = iterator_to_array($optionTb->ReadAll());

		foreach ($optionDatas as $data) {
			$datas["optionDatas"][$data["idx"]] = $data["text"];
		}

		return $datas;
	}

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

	public function createAction() {
		$post = $this->params()->fromPost();

		$optionTb = $this->getServiceLocator()->get("OptionTable");
		$post["status"] = $optionTb->ReadByText("新規")["idx"];

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		if (isset($post["idx"])) {
			$questionTb->UpdateQuestiont($post);
			die("success");
		}
		$questionTb->CreateQuestion($post);

		die("success");
	}

	public function approveAction() {
		$post = $this->params()->fromPost();
		$idxDatas = explode(",", $post["idxs"]);

		$session = new Container("user");

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		foreach ($idxDatas as $idx) {
			$result = $questionTb->ReadByIdx($idx);
			if ($result["date_approve"] != null) { die("fail"); }
		}

		foreach ($idxDatas as $idx) {
			$questionTb->UpdateToRegistByIdx($idx, $session["code"]);
		}
		die("success");
	}

	public function updateAction() {
		$post = $this->params()->fromPost();

		$whereData = ["idx" => $post["idx"]];
		unset($post["idx"]);

		$optionTb = $this->getServiceLocator()->get("OptionTable");
		$post["status"] = $optionTb->ReadByText("承認依頼")["idx"];

		$post["admin_approve"] = null;
		$post["date_approve"] = null;

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		$questionTb->UpdateQuestion($whereData, $post);
		die("success");
	}

	public function deleteAction() {
		$post = $this->params()->fromPost();
		$idxDatas = explode(",", $post["idxs"]);

		$session = new Container("user");


		$optionTb = $this->getServiceLocator()->get("OptionTable");
		$status = $optionTb->ReadByText("削除")["idx"];
		$questionTb = $this->getServiceLocator()->get("QuestionTable");

		foreach ($idxDatas as $idx) {
			$whereData = ["idx" => $idx];
			$setData = ["status" => $status, "admin_delete" => $session["code"], "date_delete" => date("Y-m-d H:i:s")];
			$questionTb->UpdateQuestion($whereData, $setData);
		}
		die("success");
	}

	public function savecsvAction() {
		$post = $this->params()->fromPost();
		$csvStrings = explode("\n", $post["csv"]);

		$csvStrings[0] = str_replace("\r", "", $csvStrings[0]);
		$csvKeys = explode(",", $csvStrings[0]);
		unset($csvStrings[0]);

		$csvDatas = array();

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		
		foreach ($csvStrings as $i => $string) {
			$string = str_replace("\r", "", $string);
			$keyIndex = 0;
			while ($string != "") {
			$value = "";

				if ($string[0] == "\"") {
					$index = strpos($string, "\",");
					$value = substr($string, 1, $index - 1);
					$string = substr($string, $index + 2, strlen($string) - $index);
				}
				else {
					$index = strpos($string, ",");
					$value = substr($string, 0, $index);
					$string = substr($string, $index + 1, strlen($string) - $index);
				}

				$csvDatas[$i - 1][$csvKeys[$keyIndex]] = $value;
				$keyIndex++;
			}

			if (!array_key_exists("admin_create", $csvDatas[$i - 1])) {
				$session = new Container("user");
				$csvDatas[$i - 1]["admin_create"] = $session("code");
			}

			if (!array_key_exists("date_create", $csvDatas[$i - 1])) {
				$csvDatas[$i - 1]["date_create"] = date("Y-m-d H:i:s");
			}

			if (!array_key_exists("status", $csvDatas[$i - 1])) {
				$csvDatas[$i - 1]["status"] = 0;
			}


			$questionTb->CreateQuestion($csvDatas[$i -1]);
		}

		die(print_r($csvDatas[0]));
	}

	/*
	public function estimateAction(){


		// 메일 센더 초기화
	$mail = new MailSender();

	$this->layout("layout/none");
	// 기본 메일 전송 관련 설정 로드
			$param['config']=$this->getConfig();

			// 메일 제목 지정 (일반적으로 DB에 메일폼 테이블을 만들어서 그것을 가져와서 아래의 title contents에 넣지만, 이건 샘플이므로 간단히.)
			// 사람마다 변환해야 할 부분은 {{이렇게}} 메일폼에 넣어놓는다.
			$param['title']="{{user_name}}様、株式会社ジエンジサービスでございます。";
			$param['content']="送信する内容\n\n以下のURLから情報を登録してください。\n\n{{URL}}";

			// 사람이름이나, URL등 고유하게 변경해야 하는 것은 이렇게 처리한다.
			// 메일 제목과 내용 부분 모두 변환처리.
			$param['title']=str_replace("{{user_name}}","testTitle",$param['title']);

			// $param['content']=str_replace("{{user_name}}","変換する試験受け者名",$param['content']);
			$param['content']=str_replace("{{URL}}","個人の試験URL",$param['content']);


			// 수신자 이메일과 이름 설정
			$param['email']='spredempt@gmail.com';
			$param['name']="temp";

			// 전송
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


			return $vm;
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
*/
}