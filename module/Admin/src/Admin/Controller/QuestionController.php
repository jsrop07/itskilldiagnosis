<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
class QuestionController extends AbstractActionController
{
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
		header("Location: ./question/list");
		exit;
	}

	public function listAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断問項管理"];
		$datas["optionDatas"] = $this->GetOptionDatas();

		$printDataNum = 10;	// Number of data to output on one page

		// Data of login user
		$session = new Container("user");
		$userLevel = $session["level"];

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		$query = $this->params()->fromQuery();

		// Get Current Page
		$page = 1;
		if (isset($query["page"])) {
			$page = $query["page"];
			unset($query["page"]);
		}

		// Save Search data
		$sqlWhere = array();
		if (isset($query["approver"])) {
			$adminTb = $this->getServiceLocator()->get("AdminTable");
			$sqlWhere["admin_approve"] = $adminTb->ReadByName($query["approver"])["code"];
			$datas["searchDatas"]["approver"] = $query["approver"];
		}

		if (isset($query["title"])) {
			$sqlWhere["title"] = $query["title"];
			$datas["searchDatas"]["title"] = $query["title"];
		}

		// Save Align data
		$sqlOrder = array();
		if (isset($query["align"])) {
			$sqlOrder["field"] = explode("-", $query["align"])[0];
			$sqlOrder["seq"] = explode("-", $query["align"])[1];
			$datas["searchDatas"]["align"] = $query["align"];
		}

		$questionDatas = "";
		// Check User Level
		if ($userLevel >= 1) {
			// Check Search data and Align data
			if (!empty($sqlOrder) && !empty($sqlWhere)) {
				try { $questionDatas = $questionTb->GetListBySearchnAlign($sqlWhere, $sqlOrder); }
				catch (\Exception $e) { print_r($e->getMessage()); exit; }
			}
			else if (!empty($sqlOrder) && empty($sqlWhere)) {
				try { $questionDatas = $questionTb->GetListByAlign($sqlOrder); }
				catch (\Exception $e) { print_r($e->getMessage()); exit; }
			}
			else if (empty($sqlOrder) && !empty($sqlWhere)) {
				try { $questionDatas = $questionTb->GetListBySearch($sqlWhere); }
				catch (\Exception $e) { print_r($e->getMessage()); exit; }
			}
			else {
				try { $questionDatas = $questionTb->GetAllList(); }
				catch (\Exception $e) { print_r($e->getMessage()); exit; }
			}
		} else {
			$userCode = $session["code"];
			if (!empty($sqlOrder) && !empty($sqlWhere)) {
				try { $questionDatas = $questionTb->GetListValidBySearchnAlign($userCode, $sqlWhere, $sqlOrder); }
				catch (\Exception $e) { print_r($e->getMessage()); exit; }
			}
			else if (!empty($sqlOrder) && empty($sqlWhere)) {
				try { $questionDatas = $questionTb->GetValidListByAlign($userCode, $sqlOrder); }
				catch (\Exception $e) { print_r($e->getMessage()); exit; }
			}
			else if (empty($sqlOrder) && !empty($sqlWhere)) {
				try { $questionDatas = $questionTb->GetValidListBySearch($userCode, $sqlWhere); }
				catch (\Exception $e) { print_r($e->getMessage()); exit; }
			}
			else {
				try { $questionDatas = $questionTb->GetValidList($userCode); }
				catch (\Exception $e) { print_r($e->getMessage()); exit; }
			}
		}

		$questionDatas->setCurrentPageNumber($page);
		$questionDatas->setItemCountPerPage($printDataNum);
		$datas["questionDatas"] = $questionDatas;
		return $this->SetViewModel($datas, "/question/question_list.phtml");
	}
	
	/** When you click 新規登録 button on 一覧 page */
	public function inputAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "問題登録"];
		$datas["title"] = "問題登録";

		// Check return from 登録確認　page
		$post = $this->params()->fromPost();
		if (isset($post["title"])) {
			$datas["questionData"] = $post;
		}

		$adminTb = $this->getServiceLocator()->get("AdminTable");
		$datas["adminDatas"] = $adminTb->ReadAllList();

		$datas = $this->GetOptionDatasForInput($datas);
		return $this->SetViewModel($datas, "/question/question_input.phtml");
	}

	/** When you click 登録 button on 問題登録 page */
	public function confirmAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "問題登録", "登録確認"];
		$datas["title"] = "登録確認";

		$post = $this->params()->fromPost();

		// Change 答え data to string
		$answers = "";
		foreach ($post as $key => $value) {
			if (strpos($key, "answer") !== false) {
				$answers .= $value . ",";
				unset($post[$key]);
			}
		}
		$post["answers"] = substr($answers, 0, -1);

		$datas["printDatas"] = $post;
		$datas["inputDatas"] = $post;

		// Save register name
		$adminTb = $this->getServiceLocator()->get("AdminTable");
		$datas["register"] = $adminTb->ReadByCode($post["admin_regist"])["name"];

		$optionTb = $this->getServiceLocator()->get("OptionTable");
		$optionDatas = iterator_to_array($optionTb->ReadAll());
		foreach ($optionDatas as $data) {
			$datas["optionDatas"][$data["idx"]] = $data["text"];
		}

		return $this->SetViewModel($datas, "/question/question_confirm.phtml");
	}
	
	/** When you choose list data on 問題一覧 page */
	public function detailAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "問題詳細"];

		$datas = $this->GetOptionDatas($datas);
		$index = $this->params()->fromRoute("index");

		$questionTable = $this->getServiceLocator()->get("QuestionTable");
		$questionData = $questionTable->ReadByIdx($index);
		$datas["questionData"] = $questionData;

		// Save register name
		$adminTb = $this->getServiceLocator()->get("AdminTable");
		$datas["register"] = $adminTb->ReadByCode($questionData["admin_regist"])["name"];

		// Save approver name
		if ($questionData["date_approve"] != null) {
			$datas["approver"] = $adminTb->ReadByCode($questionData["admin_approve"])["name"];
		}

		// Save Note
		$datas["note"] = str_replace("\n", "<br/>", $datas["questionData"]["note"]);

		return $this->SetViewModel($datas, "/question/question_detail.phtml");
	}

	/** When you click 修正 button on 問題詳細 page */
	public function editAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "問題詳細", "問題修正"];
		$datas["title"] = "問題修正";

		$optionTb = $this->getServiceLocator()->get("OptionTable");
		$datas["updateStatus"] = $optionTb->ReadByText("承認依頼")["idx"];
		$datas = $this->GetOptionDatasForInput($datas);

		$idx = $this->params()->fromRoute("index");

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		$datas["questionData"] = $questionTb->ReadByIdx($idx);

		$adminTb = $this->getServiceLocator()->get("AdminTable");
		$datas["adminDatas"] = iterator_to_array($adminTb->ReadAll());

		return $this->SetViewModel($datas, "/question/question_edit.phtml");
	}

	public function csvAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断問項管理", "CSV登録"];
		return $this->SetViewModel($datas, "/question/question_csv.phtml");
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

	/** Get optionDatas
	 * @return array $optionDatas["idx" => "text"]
	*/
	function GetOptionDatas() {
		$optionTb = $this->getServiceLocator()->get("OptionTable");
		$beforeOptionDatas = iterator_to_array($optionTb->ReadAll());

		$afterOptionDatas = array();
		foreach ($beforeOptionDatas as $data) {
			$afterOptionDatas[$data["idx"]] = $data["text"];
		}

		return $afterOptionDatas;
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

	public function createAction() {
		$post = $this->params()->fromPost();

		$optionTb = $this->getServiceLocator()->get("OptionTable");
		$post["status"] = $optionTb->ReadByText("新規")["idx"];

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		$questionTb->CreateQuestion($post);

		die("success");
	}

	public function approveAction() {
		$idxs = $this->params()->fromPost("idxs");
		$idxDatas = explode(",", $idxs);

		$session = new Container("user");

		$questionTb = $this->getServiceLocator()->get("QuestionTable");

		$beforeNotes = array();
		foreach ($idxDatas as $idx) {
			$result = $questionTb->ReadByIdx($idx);
			if ($result["date_approve"] != null) { die("fail"); }
			else { array_push($beforeNotes, $result["note"]); }
		}

		$optionTb = $this->getServiceLocator()->get("OptionTable");
		$sqlSet["status"] = $optionTb->ReadByText("承認済")["idx"];
		$sqlSet["admin_approve"] = $session["code"];
		$sqlSet["date_approve"] = date("Y-m-d H:i:s");
		$log = "承認　" . date("Y.m.d") . "　" . $session["name"] . "\n";

		foreach ($idxDatas as $index => $idx) {
			$sqlSet["note"] = $beforeNotes[$index] . $log;
			$questionTb->UpdateByIdx($idx, $sqlSet);
		}

		die("success");
	}

	public function updateAction() {
		$post = $this->params()->fromPost();

		$idx = ["idx" => $post["idx"]];
		unset($post["idx"]);

		$optionTb = $this->getServiceLocator()->get("OptionTable");
		if(!isset($post["status"])) { $post["status"] = $optionTb->ReadByText("承認依頼")["idx"]; }

		if(isset($post["admin_approve"]) && $post["admin_approve"] == "null") { $post["admin_approve"] = null; }
		if(isset($post["date_approve"]) && $post["date_approve"] == "null") { $post["date_approve"] = null; }
		else { $post["date_approve"] = date("Y-m-d H:i:s"); }

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		$questionTb->UpdateByIdx($idx, $post);

		die("success");
	}

	public function removeAction() {
		$post = $this->params()->fromPost();
		$idxDatas = explode(",", $post["idxs"]);

		$session = new Container("user");
		$sqlSet["admin_delete"] = $session["code"];

		$optionTb = $this->getServiceLocator()->get("OptionTable");
		$sqlSet["status"] = $optionTb->ReadByText("削除")["idx"];

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		foreach ($idxDatas as $idx) {	
			$questionTb->DeleteQuestion($idx, $sqlSet);
		}
		die("success");
	}

	public function createByCsvAction() {
		if ($_FILES["csv_file"]["error"] == "0") {
			header("Content-Type: text/html; charset=utf-8");

			$filePointer = fopen($_FILES["csv_file"]["tmp_name"], "r");
			if (!$filePointer) { die("ファイル　オープン　失敗"); }

			$csvStrings = array();
			while($line = fgetcsv($filePointer, 1024, ",")) {
				array_push($csvStrings, $line);
			}

			$keys = $csvStrings[0];
			foreach ($keys as $idx => $data) {
				$keys[$idx] = preg_replace("/[^A-Za-z0-9-]/", "", $data);
			}
			unset($csvStrings[0]);

			$session = new Container("user");
			$userCode = $session["code"];

			$questionTb = $this->getServiceLocator()->get("QuestionTable");
			$optionTb = $this->getServiceLocator()->get("OptionTable");

			$questionData = array();
			foreach ($csvStrings as $csvDatas) {
				foreach ($csvDatas as $idx => $data) {
					$questionData[$keys[$idx]] = $data;
				}

				$questionData["class1st"] = $optionTb->ReadByText([$questionData["class1st"]])["idx"];
				$questionData["class2nd"] = $optionTb->ReadByText([$questionData["class2nd"]])["idx"];
				$questionData["level"] = $optionTb->ReadByText([$questionData["level"]])["idx"];
				$questionData["type"] = $optionTb->ReadByText([$questionData["type"]])["idx"];
				$questionData["note"] = "create by csv";
				$questionData["status"] = $optionTb->ReadByText(["新規"])["idx"];
				$questionData["admin_regist"] = $userCode;
				$questionData["date_regist"] = date("Y-m-d H:i:s");

				$questionTb->CreateQuestion($questionData);
			}
		}

		die("success");
	}

	public function sortArrByKey($arr, $alignData) {
		$optionTb = $this->getServiceLocator()->get("OptionTable");

		$key = explode("_", $alignData)[0];
		$align = explode("_", $alignData)[1];

		$tempArr = array();
		if ($key == "regist") {
			$key = "date_regist";
			foreach ($arr as $idx => $data) {
				$tempArr[$idx] = $data[$key];
			}
		} else {
			foreach ($arr as $idx => $data) {
				$data[$key] = $optionTb->ReadOption(["idx" => $data[$key]])["text"];
				$arr[$idx][$key] = $data[$key];
				$tempArr[$idx] = $data[$key];
			}
		}

		if ($align == "ASC") { array_multisort($tempArr, SORT_ASC, $arr); }
		else { array_multisort($tempArr, SORT_DESC, $arr); }
		unset($tempArr);

		if ($key == "date_regist") { return $arr; }

		foreach ($arr as $idx => $data) {
			$arr[$idx][$key] = $optionTb->ReadOption(["text" => $data[$key]])["idx"];
		}

		return $arr;
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