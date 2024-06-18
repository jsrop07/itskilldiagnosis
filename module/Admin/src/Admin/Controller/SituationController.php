<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Admin\Model\MailRequest;
/*
	作成：朴昰成
	作成日：24/06/17
*/
use Admin\Model\LogModule;
/* ここまで */

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
		$paginationData = "";

		$recordDatas = array();
		$offset = ($page - 1) * 10;
		if (!empty($query)) {
			$sqlWhere = array();
			$datas["searchDatas"] = $query;

			if (isset($query["name"])) { $sqlWhere["name"] = $query["name"]; }
			if (isset($query["date"])) { $sqlWhere["date"] = $query["date"]; }

			if (isset($query["select"])) {
				$data = explode("-", $query["select"]);

				switch ($data[0]) {
					case "status":
						switch ($data[1]) {
							case "apply":
								$sqlWhere["request_date"] = "null";
								break;
							case "request":
								$sqlWhere["request_date"] = "not null";
								$sqlWhere["rank"] = "null";
								break;
							case "execute":
								$sqlWhere["request_date"] = "not null";
								$sqlWhere["execute_date"] = "not null";
								$sqlWhere["rank"] = "A";
								$sqlWhere["rank"] = "B";
								$sqlWhere["rank"] = "C";
								$sqlWhere["rank"] = "D";
								break;
							case "unexecute":
								$sqlWhere["request_date"] = "not null";
								$sqlWhere["execute_date"] = "not null";
								$sqlWhere["rank"] = "F";
								break;
						}
						break;
					case "education":
						switch ($data[1]) {
							case "high":
								$sqlWhere["education"] = "高卒";
								break;
							case "voca":
								$sqlWhere["education"] = "専門卒";
								break;
							case "uni":
								$sqlWhere["education"] = "大卒";
								break;
							case "grad":
								$sqlWhere["education"] = "大学院卒";
								break;
						}
						break;
					default:
						$sqlWhere[$data[0]] = $data[1];
						break;
				}
			}
			try { $newRecordDatas = $recordTb->ReadNewListBySearchnOffset($sqlWhere, $offset); }
			catch (\Exception $e) { print_r($e->getMessage()); exit; }

			if (count($newRecordDatas) < 10) {
				$limit = 10 - count($newRecordDatas);
				$offset -= $recordTb->CountNewDataBySearch($sqlWhere);
				if ($offset < 0) { $offset = 0; }

				try { $restRecordDatas = $recordTb->ReadRestListBySearchnOffsetnLimit($sqlWhere, $offset, $limit); }
				catch (\Exception $e) { print_r($e->getMessage()); exit; }

				$recordDatas = array_merge($newRecordDatas, $restRecordDatas);
			}
			else {
				$recordDatas = $newRecordDatas;
			}
			try { $paginationData = $recordTb->GetListBySearch($sqlWhere); }
			catch (\Exception $e) { print_r($e->getMessage()); exit; }
		}
		else {
			try { $newRecordDatas = $recordTb->ReadNewListByOffset($offset); }
			catch (\Exception $e) { print_r($e->getMessage()); exit; }

			if (count($newRecordDatas) <= 10) {
				$limit = 10 - count($newRecordDatas);
				$offset -= $recordTb->CountNewData();
				if ($offset < 0) { $offset = 0; }

				try { $restRecordDatas = $recordTb->ReadRestListByOffsetnLimit($offset, $limit); }
				catch (\Exception $e) { print_r($e->getMessage()); exit; 
				}

				$recordDatas = array_merge($newRecordDatas, $restRecordDatas);
			}
			else {
				$recordDatas = $newRecordDatas;
			}
			$paginationData = $recordTb->GetAllList();
		}


			try {
				$datas["totalApply"] = $recordTb->CountApplyData();
				$datas["totalRequest"] = $recordTb->CountRequestData();
				$datas["countOver"] = $recordTb->CountOverData();
				$datas["totalData"] = $recordTb->CountAllData();
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

				if (is_null($data["request_date"])) {
					$data["status"] = "新規";
				}
				else {
					if (is_null($data["rank"])) { $data["status"] = "診断"; }
					else if ($data["rank"] == "F") { $data["status"] = "失格"; }
					else { $data["status"] = "終了"; }
				}

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

		$diagnosisData = $diagnosisTb->ReadForRecordByCodenDate($recordData["diagnosis_code"], $recordData["diagnosis_date"]);
		$recordData = array_merge($diagnosisData, $recordData);
		
		$datas = $this->GetOptionDatas($datas);

		$datas["recordData"] = $recordData;

		return $this->SetViewModel($datas, "/situation/situation_detail.phtml");
	}

	/** When you click 新規登録 button on 診断者一覧 page */
	function requestAction() {
		$idxs = $this->params()->fromPost("idxs");
		$recordIdxs = explode(",", $idxs);

		/*
			作成：朴昰成
			作成日：24/06/17
		*/
		$LogModule = new LogModule();
		/* ここまで */
		$applicantTb = $this->getServiceLocator()->get("ApplicantTable-Admin");
		$recordTb = $this->getServiceLocator()->get("RecordTable-Admin");
		$adminTb = $this->getServiceLocator()->get("AdminTable");

		$recordDatas = array();
		$errorRecordDatas = array();
		foreach ($recordIdxs as $idx) {
			/*
				作成：朴昰成
				修正：朴昰成
				修正日：24/06/17
			*/

			/* 修正前：
			try { $recordData = $recordTb->ReadByIdx($idx); }
			catch (\Exception $e) { die($e->getMessage()); }
			*/

			/* 修正後： */
			try {
				$recordData = $recordTb->ReadByIdx($idx);
			} catch (\Exception $e) {
				$logData["reason"] = "exception at SituationController requestAction RecordTable ReadByIdx";
				$logData["message"] = $e->getMessage();
				$log = $LogModule->SaveLog($logData);
				die($log);
			}
			/* ここまで */

			if ($recordData["request_date"] != null) { $errorRecordDatas[] = $recordData; }
			$recordDatas[] = $recordData;
		}

		if ($errorRecordDatas) {
			/*
				作成：朴昰成
				修正：朴昰成
				修正日：24/06/16
			*/

			/* 修正前：
			$applicnatDatas = array();
			foreach ($errorRecordDatas as $recordData) {
				try { $applicnatDatas[] = $applicantTb->ReadByIdx($recordData["applicant_idx"]); }
				catch (\Exception $e) { die($e->getMessage()); }
			}
			die(json_encode($applicnatDatas));
			*/

			/* 修正後： */
			die(json_encode($errorRecordDatas));
			/* ここまで */
		}

		/*
			作成：朴昰成
			修正：朴昰成
			修正日：24/06/17
		*/

		/* 修正前：
		try { $PICDatas = $adminTb->ReadPIC(); }
		catch (\Exception $e) { die($e->getMessage()); }

		foreach ($PICDatas as $adminData) {
			foreach ($recordDatas as $recordData) {
				try { $applicantData = $applicantTb->ReadByIdx($recordData["applicant_idx"]); }
				catch (\Exception $e) { die($e->getMessage()); }

				$skillText = "無";
				if ($recordData["skill"] == 0) { $skillText = "有"; }

				$caseText = "中途（経歴職）";
				if($recordData["case"] == 0){ $caseText = "新卒"; }

				$this->mailByRequest($adminData, $applicantData);
				$this->mailByAdmin($applicantData, $skillText, $caseText, $adminData, $recordData);

				try { $recordTb->RequestByIdx($recordData["idx"]); }
				catch (\Exception $e) { die($this->SaveLog($e->getMessage())); }
			}
		}
		*/

		/* 修正後： */
		try {
			$PICData = $adminTb->ReadPIC()[0];
		} catch (\Exception $e) {
			$logData["reason"] = "exception at SituationController requestAction AdminTable ReadPIC";
			$logData["message"] = $e->getMessage();
			$log = $LogModule->SaveLog($logData);
			die($log);
		}

		foreach ($recordDatas as $recordData) {
			try {
				$applicantData = $applicantTb->ReadByIdx($recordData["applicant_idx"]);
			} catch (\Exception $e) {
				$logData["reason"] = "exception at SituationController requestAction ApplicantTable ReadByIdx";
				$logData["message"] = $e->getMessage();
				$log = $LogModule->SaveLog($logData);
				die($log);
			}

			$skillText = "無";
			if ($recordData["skill"] == 0) { $skillText = "有"; }

			$caseText = "中途（経歴職）";
			if($recordData["case"] == 0){ $caseText = "新卒"; }

			$this->mailByRequest($PICData, $applicantData);
			$this->mailByAdmin($applicantData, $skillText, $caseText, $PICData, $recordData);

			try {
				$recordTb->RequestByIdx($recordData["idx"]);
			} catch (\Exception $e) {
				$logData["reason"] = "exception at SituationController requestAction RecordTable RequestByIdx";
				$logData["message"] = $e->getMessage();
				$log = $LogModule->SaveLog($logData);
				die($log);
			}
		}
		/* ここまで */

		die("success");
	}

	/** When Send Mail for Notice Result */
	public function mailAction() {
		$idxs = $this->params()->fromPost("idxs");
		$idxDatas = explode(",", $idxs);
		
		/*
			作成：朴昰成
			作成日：24/06/17
		*/
		$LogModule = new LogModule();
		/* ここまで */
		$recordTb = $this->getServiceLocator()->get("RecordTable-Admin");

		$recordDatas = array();
		$recordIdxDatas = array();
		foreach ($idxDatas as $idx) {
			$recordData = "";
			/*
				作成：朴昰成
				修正：朴昰成
				修正日：24/06/17
			*/

			/* 修正前：
			try { $recordData = $recordTb->ReadByIdx($idx); }
			catch (\Exception $e) { die($e->getMessage()); }
			*/

			/* 修正後： */
			try {
				$recordData = $recordTb->ReadByIdx($idx);
			} catch (\Exception $e) {
				$logData["reason"] = "exception at SituationController mailAction RecordTable ReadByIdx";
				$logData["message"] = $e->getMessage();
				$log = $LogModule->SaveLog($logData);
				die($log);
			}
			/* ここまで */

			// when test didn't ended	
			if ($recordData["rank"] == null) { $recordIdxDatas[] = $recordData; }
			// when mail already sent
			if ($recordData["date_mail"] != null) { $recordIdxDatas[] = $recordData; }

			$recordDatas[] = $recordData;
		}

		// return record idx that sent mail
		if (!empty($recordIdxDatas)) {
			$applicantTb = $this->getServiceLocator()->get("ApplicantTable-Admin");

			$applicantDatas = array();
			foreach ($recordIdxDatas as $recordData) {
				/*
					作成：朴昰成
					修正：朴昰成
					修正日：24/06/17
				*/
	
				/* 修正前：
				$applicantDatas[] = $applicantTb->ReadByIdx($recordData["applicant_idx"]);
				*/
	
				/* 修正後： */
				try {
					$applicantDatas[] = $applicantTb->ReadByIdx($recordData["applicant_idx"]);
				} catch (\Exception $e) {
					$logData["reason"] = "exception at SituationController mailAction ApplicantTable ReadByIdx";
					$logData["message"] = $e->getMessage();
					$log = $LogModule->SaveLog($logData);
					die($log);
				}
				/* ここまで */
			}

			die(json_encode($applicantDatas));
		}

		// read pic_admin data
		$adminTb = $this->getServiceLocator()->get("AdminTable");
		/*
			作成：朴昰成
			修正：朴昰成
			修正日：24/06/17
		*/

		/* 修正前：
		$PicDatas = "";
		try { $PicDatas = $adminTb->ReadPIC(); }
		catch (\Exception $e) { die($e->getMessage()); }

		// send mail
		$applicantTb = $this->getServiceLocator()->get("ApplicantTable-Admin");
		foreach ($recordDatas as $data) {
			// read applicant data
			$applicantData = "";
			try { $applicantData = $applicantTb->ReadByIdx($data["applicant_idx"]); }
			catch (\Exception $e) { die($e->getMessage()); }

			$result = $this->SendResultMailToApplicantByPICAdmin($applicantData, $data, $PicDatas[0]);
			if ($result == "exception" || $result == "fale") { return "fail"; }

			// update applicant table
			$sqlSet["date_mail"] = date("Y-m-d H:i:s");
			try {
				$recordTb->UpdateByIdx($data["idx"], $sqlSet);
			}
			catch (\Exception $e) {
				$this->SaveLog($e->getMessage());
				return json_encode($applicantData);
			}
		}
		*/

		/* 修正後： */
		$PicData = "";
		try {
			$PicData = $adminTb->ReadPIC()[0];
		} catch (\Exception $e) {
			$logData["reason"] = "exception at SituationController mailAction AdminTable ReadPIC";
			$logData["message"] = $e->getMessage();
			$log = $LogModule->SaveLog($logData);
			die($log);
		}

		// send mail
		$applicantTb = $this->getServiceLocator()->get("ApplicantTable-Admin");
		foreach ($recordDatas as $data) {
			// read applicant data
			$applicantData = "";
			try {
				$applicantData = $applicantTb->ReadByIdx($data["applicant_idx"]);
			} catch (\Exception $e) {
				$logData["reason"] = "exception at SituationController mailAction ApplicantTable ReadByIdx";
				$logData["message"] = $e->getMessage();
				$log = $LogModule->SaveLog($logData);
				die($log);
			}

			$result = $this->SendResultMailToApplicantByPICAdmin($applicantData, $data, $PicData);
			if ($result == "exception" || $result == "fale") { return "mail failed"; }

			// update applicant table
			$sqlSet["date_mail"] = date("Y-m-d H:i:s");
			try {
				$recordTb->UpdateByIdx($data["idx"], $sqlSet);
			}
			catch (\Exception $e) {
				$logData["reason"] = "exception at SituationController mailAction RecordTable UpdateByIdx";
				$logData["message"] = $e->getMessage();
				$log = $LogModule->SaveLog($logData);
				die($log);
			}
		}
		/* ここまで */

		die("success");
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
		$optionDatas = ($optionTb->ReadAll());

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
		$optionDatas = ($optionTb->ReadValid());

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

// 修正の時
/* log
	作成：丁錫圓
	修正：丁錫圓
	修正日：24/06/10
*/

/* 修正前：
	function mailByRequest($managerInfo,$recentPassword,$recordSet){
		$mail = new MailRequest();

		// 기본 메일 전송 관련 설정 로드
		$param['config']=$this->getConfig();
		// 메일 제목 지정 (일반적으로 DB에 메일폼 테이블을 만들어서 그것을 가져와서 아래의 title contents에 넣지만, 이건 샘플이므로 간단히.)
		// 사람마다 변환해야 할 부분은 {{이렇게}} 메일폼에 넣어놓는다.

			$param['title']="ITスキル診断依頼のお知らせ（ジエンジサービス）";
		$param["content"] = "{{applicant_name}}様\n"
											. "お世話になっております。\n\n"
											. "ITスキル診断についてお知らせさせていただきます。\n"
                      . "以下URLより「ITスキル診断サイト」にログインし診断を行ってください。\n\n"
											. "ログインID：{{login_id}}\n"
											. "ログインPWD：{{login_password}}\n\n"
											. "＜ITスキル診断URL＞\n"
											. "http://18.181.4.65/applicant/login\n\n"
											. "※ITスキル診断が可能な有効期限は{{dateSchedule}}分 ~ {{dateSchduleEnd}}です。\n"
											. "   有効期限内に受験を受けない場合、自動的に失格となりますのでご了承ください。\n\n"
											. "※ITスキル診断に不明点などございましたら下記の宛先まで\n"
											. "   お問い合わせください。\n\n"
											. "＜問い合わせ先＞\n"
											. "担当者：ITスキル診断担当\n"
											. "連絡先：tech@gngs.co.jp\n\n"
											. "以上、よろしくお願いいたします。\n"
											. "※このメールに返信しないでください。";
		$param["content"] = str_replace("{{applicant_name}}", $recentPassword["name"], $param["content"]);
		$param["content"] = str_replace("{{login_id}}", $recentPassword["email"], $param["content"]);
		$param["content"] = str_replace("{{login_password}}", $recentPassword["password"], $param["content"]);
		$param["content"] = str_replace("{{dateSchedule}}", $applicantInfo["date_schedule"], $param["content"]);
		$param["content"] = str_replace("{{dateSchduleEnd}}", $dateSchduleEnd, $param["content"]);
*/

/* 修正後： */
	function mailByRequest($managerInfo,$recentPassword){
		$mail = new MailRequest();
		$applicantTb = $this->getServiceLocator()->get("ApplicantExamTable");

		$applicantInfo = $applicantTb->readByApplicantIdx($recentPassword);
		$dateSchduleEnd = date("Y-m-d H:i:s", strtotime($applicantInfo["date_schedule"] . ' +30 minutes'));
		// 기본 메일 전송 관련 설정 로드
		$param['config']=$this->getConfig();
		// 메일 제목 지정 (일반적으로 DB에 메일폼 테이블을 만들어서 그것을 가져와서 아래의 title contents에 넣지만, 이건 샘플이므로 간단히.)
		// 사람마다 변환해야 할 부분은 {{이렇게}} 메일폼에 넣어놓는다.

		$param['title']="ITスキル診断依頼のお知らせ（ジエンジサービス）";
		$param["content"] = "{{applicant_name}}様\n"
											. "お世話になっております。\n\n"
											. "ITスキル診断についてお知らせさせていただきます。\n"
                      . "以下URLより「ITスキル診断サイト」にログインし診断を行ってください。\n\n"
											. "ログインID ：{{login_id}}\n"
											. "ログインPWD：{{login_password}}\n\n"
											. "＜ITスキル診断URL＞\n"
											. "{$param['config']['user-url']['applicant']}/login\n\n"
											. "※ITスキル診断が可能な有効期限は{{dateSchedule}}分 ~ {{dateSchduleEnd}}です。\n"
											. "   有効期限内に受験を受けない場合、自動的に失格となりますのでご了承ください。\n\n"
											. "※ITスキル診断に不明点などございましたら下記の宛先まで\n"
											. "   お問い合わせください。\n\n"
											. "＜問い合わせ先＞\n"
											. "担当者：ITスキル診断担当\n"
											. "連絡先：tech@gngs.co.jp\n\n"
											. "以上、よろしくお願いいたします。\n"
											. "※このメールに返信しないでください。";
/* ここまで */

		// print_r($param['title']);
		// $param['content']=str_replace("{{user_name}}","変換する試験受け者名",$param['content']);
		$param["content"] = str_replace("{{applicant_name}}", $recentPassword["name"], $param["content"]);
		$param["content"] = str_replace("{{login_id}}", $recentPassword["email"], $param["content"]);
		$param["content"] = str_replace("{{login_password}}", $recentPassword["password"], $param["content"]);
		$param["content"] = str_replace("{{dateSchedule}}", $applicantInfo["date_schedule"], $param["content"]);
		$param["content"] = str_replace("{{dateSchduleEnd}}", $dateSchduleEnd, $param["content"]);
		// 수신자 이메일과 이름 설정
		$param['managerEmail']=$managerInfo["id"];
		$param['email']=$recentPassword["email"];;
		$param['password']= $managerInfo["password"];
		$param['name']= $managerInfo["name"];
		$param['smtp_password']=$managerInfo["smtp_password"];

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
					die($result);
						break;
		}
	  }

    function mailByAdmin($arr,$skillText,$caseText,$managerInfo,$applicantInfo){
      $mail = new MailRequest();
      

      // 기본 메일 전송 관련 설정 로드
      $param['config']=$this->getConfig();
      // 메일 제목 지정 (일반적으로 DB에 메일폼 테이블을 만들어서 그것을 가져와서 아래의 title contents에 넣지만, 이건 샘플이므로 간단히.)
      // 사람마다 변환해야 할 부분은 {{이렇게}} 메일폼에 넣어놓는다.
      $param['title']="{{user_name}}様、新しい試験診断の申し込みがあります。";
      $param["content"] = "以下の申込者の情報をご参照ください。\n\nお名前（漢字）：{$arr["name"]}\nお名前（カナ）：{$arr["kana"]}\n応募区分：{$caseText}\nITスキル：{$skillText}\n\n診断者ページ：{$param['config']['user-url']['admin']}/situation/detail/{$applicantInfo["idx"]}";
    
      // 사람이름이나, URL등 고유하게 변경해야 하는 것은 이렇게 처리한다.
      // 메일 제목과 내용 부분 모두 변환처리.
      $param['title']=str_replace("{{user_name}}","申し込み担当者",$param['title']);
      // print_r($param['config']);
      // $param['content']=str_replace("{{user_name}}","変換する試験受け者名",$param['content']);
      $param['content']=str_replace("{{URL}}","テスト",$param['content']);
    

      // 수신자 이메일과 이름 설정
			$param['email']=$managerInfo["id"];;
			$param['password']= $managerInfo["password"];
			$param['name']= $managerInfo["name"];
			$param['smtp_password']=$managerInfo["smtp_password"];

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
						die($result);
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
		
		public function inputAction() {
			$this->ChkLogin();
			$datas["breadcrumbData"] = ["ITスキル診断状況管理", "診断者登録"];
			$post = $this->params()->fromPost();
	
			$situTb = $this->getServiceLocator()->get("situTable");
	
			if (isset($post["class2nd"]) && isset($post['level'])) {
				$result = $situTb->ReadDiagnosis($post["class2nd"], $post["level"]);
				die(json_encode($result));
				
			}
	
			$datas["optionDatas"] = $this->GetOptionDatasForInput2();
	
			$class1st=$situTb->getclass1st();
			$class2nd=$situTb->getclass2nd();
	
	
			$datas["class1st"] = $class1st;
			$datas["class2nd"] = $class2nd;
	
			
			return $this->SetViewModel($datas, "/situation/situation_input.phtml");
		}
	
		public function inputOkAction() {
			$post = $this->params()->fromPost();
			$inputDatas  = (isset($post['inputDatas']) && $post['inputDatas'] !='')  ? $post['inputDatas'] : '';
	
			$situTb = $this->getServiceLocator()->get("situTable");
	
			
			$managerInfo=$situTb->readByManagerInfo();
			
			// $managerArray=[$managerInfo["id"], $managerInfo["password"],$managerInfo["name"],$managerInfo["smtp_password"]];		
	
			// $applicantInfo = $situTb->readById($post['recordindex']);
	
			if($inputDatas == "btn_submit"){
				$email = $this->params()->fromPost('email');
				$password = $this->params()->fromPost('password');
				$name = $this->params()->fromPost('name');
				$kana = $this->params()->fromPost('kana');
				$gender = $this->params()->fromPost('gender');
				$birth = $this->params()->fromPost('birth');
				$case = $this->params()->fromPost('case');
				$education = $this->params()->fromPost('education');
				$major = $this->params()->fromPost('major');
				$skill = $this->params()->fromPost('skill');
				$class1st = $this->params()->fromPost('class1st');
				$class2nd = $this->params()->fromPost('class2nd');
				$career = $this->params()->fromPost('career');
				$certificates = $this->params()->fromPost('certificates');
				$other = $this->params()->fromPost('other');	
				$code = $this->params()->fromPost('code');	
				$method = $this->params()->fromPost('method');	
				$language = $this->params()->fromPost('language');	
				/*
					作成：丁錫圓
					作成日：24/05/29
				*/
				$mail_delay = $this->params()->fromPost('mail_delay');	
				$schedule = $this->params()->fromPost('schedule');	
				/* ここまで */

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
					'password' => $password,
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
					'code' => $code,
					'method' => $method,
					/*
						作成：丁錫圓
						修正：丁錫圓
						修正日：24/05/29
					*/

					/* 修正前：
					'language' => $language
					*/

					/* 修正後： */
					'language' => $language,
					'mail_delay' => $mail_delay,
					'schedule' => $schedule
					/* ここまで */
				];      
				$situTb->insertAndUpdateApplication($arr);
				 
				$applicantInfo = $situTb->getRecord();

				$this->mailByAdmin($arr,$skillText,$caseText,$managerInfo,$applicantInfo);
				/*
					作成：丁錫圓
					修正：朴昰成
					修正日：24/06/18
				*/

				/* 修正前：
				echo "
				<script>
				alert('依頼が完了しました')
				self.location.href='/admin/situation/list';
				</script>
				";	
				*/

				/* 修正後： */
				echo "
				<script>
				alert('依頼しました')
				self.location.href='/admin/situation/list';
				</script>
				";	
				/* ここまで */
		
				exit;
			} elseif($inputDatas == "btn_save"){
				$email = $this->params()->fromPost('email');
				$password = $this->params()->fromPost('password');
				$name = $this->params()->fromPost('name');
				$kana = $this->params()->fromPost('kana');
				$gender = $this->params()->fromPost('gender');
				$birth = $this->params()->fromPost('birth');
				$case = $this->params()->fromPost('case');
				$education = $this->params()->fromPost('education');
				$major = $this->params()->fromPost('major');
				$skill = $this->params()->fromPost('skill');
				$class1st = $this->params()->fromPost('class1st');
				$class2nd = $this->params()->fromPost('class2nd');
				$career = $this->params()->fromPost('career');
				$certificates = $this->params()->fromPost('certificates');
				$other = $this->params()->fromPost('other');	
				$code = $this->params()->fromPost('code');	
				$method = $this->params()->fromPost('method');	
				$language = $this->params()->fromPost('language');	
				/*
					作成：丁錫圓
					作成日：24/05/29
				*/
				$mail_delay = $this->params()->fromPost('mail_delay');	
				$schedule = $this->params()->fromPost('schedule');	
				/* ここまで */
				$saveArr=[
						'email' => $email,
						'password' => $password,
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
						'code' => $code,
						'method' => $method,
						'language' => $language,
						/*
							作成：丁錫圓
							作成日：24/05/29
						*/
						'mail_delay' => $mail_delay,
						'schedule' => $schedule,
						/* ここまで */			
						'save' => "save"
					];
				$situTb->insertAndUpdateApplication($saveArr);

				echo "
				<script>
				alert('保存しました。')
				self.location.href='/admin/situation/list';
				</script>
				";	
				}
		}

		public function editAction() {
			$this->ChkLogin();
			$datas["breadcrumbData"] = ["ITスキル診断状況管理", "診断状況詳細", "診断状況修正"];
			$index = $this->params()->fromRoute("index");
			$post = $this->params()->fromPost();
	
			$recordTb = $this->getServiceLocator()->get("RecordTable-Admin");
			$applicantTb = $this->getServiceLocator()->get("ApplicantTable-Admin");
			$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");
			$situTb = $this->getServiceLocator()->get("situTable");
	
			$recordData = $recordTb->ReadByIdx($index);

			$applicantData = $applicantTb->ReadByIdx($recordData["applicant_idx"]);

			$recordData = array_merge($applicantData, $recordData);

			if (($recordData["diagnosis_code"]) != null) {
				$diagnosisData = $diagnosisTb->ReadByCode($recordData["diagnosis_code"]);
				$recordData = array_merge($diagnosisData, $recordData);
			}
	
			if (isset($post["class2nd"]) && isset($post['level'])) {
				$result = $situTb->ReadDiagnosis($post["class2nd"], $post["level"]);
				die(json_encode($result));
			}
			
			// $diagnosisData = $diagnosisTb->ReadByCode($recordData["diagnosis_code"]);
			
			$datas["optionDatas"] = $this->GetOptionDatasForInput2();
			
			$class1st=$situTb->getclass1st();
			$class2nd=$situTb->getclass2nd();
	
			$datas["index"] =$index;
	
			$datas["class1st"] = $class1st;
			$datas["class2nd"] = $class2nd;
	
			$datas["applicantArray"] = $applicantData;
			$datas["recordArray"] = $recordData;
			
			// $datas["diagnosisArray"] = $diagnosisData;
			$datas["diagnosisData"] = $diagnosisTb->ReadForRecordByCodenDate($recordData["diagnosis_code"], $recordData["diagnosis_date"]);

			return $this->SetViewModel($datas, "/situation/situation_edit.phtml");
		}
	
		public function editOkAction() {
			$post = $this->params()->fromPost();
			$editDatas  = (isset($post['editDatas']) && $post['editDatas'] !='')  ? $post['editDatas'] : '';
			$recordTb = $this->getServiceLocator()->get("RecordTable-Admin");
			$situTb = $this->getServiceLocator()->get("situTable");
			$managerInfo=$situTb->readByManagerInfo();

			$applicantInfo = $recordTb->ReadByIdx($post['recordindex']);
	
			$applicantInfos = $situTb->readById($applicantInfo['applicant_idx']);
	
			if($editDatas == "btn_submit"){
				$recordlWhere['idx']=$post['recordindex'];
				$applicantWhere['idx']=$post['applicantindex'];
				$applicantSet['email']=$post['email'];
				$applicantSet['password']=$post['password'];
				$applicantSet['name']=$post['name'];
				$applicantSet['kana']=$post['kana'];
				$applicantSet['birth']=$post['birth'];
				$applicantSet['gender']=$post['gender'];
				$recordSet['case']=$post['case'];
				$recordSet['education']=$post['education'];
				$applicantSet['career']=$post['career'];
				$applicantSet['certificates']=$post['certificates'];
				$applicantSet['other']=$post['other'];
				$recordSet['major']=$post['major'];
				$recordSet['skill']=$post['skill'];
				$recordSet['class1st']=$post['class1st'];
				$recordSet['class2nd']=$post['class2nd'];
				$recordSet['diagnosis_code']=$post['code'];
				$recordSet['method']=$post['method'];
				$recordSet['language']=$post['language'];
				/*
					作成：丁錫圓
					作成日：24/05/29
				*/
				$recordSet['mail_delay']=$post['mail_delay'];
				$recordSet['date_schedule']=$post['schedule'];
				/* ここまで */
				$situTb->updateRecordInfo($recordlWhere, $recordSet);	
				$situTb->updateApplicantInfo($applicantWhere, $applicantSet);
				$recentPassword = $situTb->readById($applicantInfos);
	
				$this->mailByRequest($managerInfo,$recentPassword);
	
				echo "
				<script>
				alert('依頼が完了しました。')
				self.location.href='/admin/situation/list';
				</script>
				";	
			}
			elseif($editDatas == "btn_save"){
				$recordlWhere['idx']=$post['recordindex'];
				$applicantWhere['idx']=$post['applicantindex'];
				$applicantSet['email']=$post['email'];
				$applicantSet['password']=$post['password'];
				$applicantSet['name']=$post['name'];
				$applicantSet['kana']=$post['kana'];
				$applicantSet['birth']=$post['birth'];
				$applicantSet['gender']=$post['gender'];
				$recordSet['case']=$post['case'];
				$recordSet['education']=$post['education'];
				$applicantSet['career']=$post['career'];
				$applicantSet['certificates']=$post['certificates'];
				$applicantSet['other']=$post['other'];
				$recordSet['major']=$post['major'];
				$recordSet['skill']=$post['skill'];
				$recordSet['class1st']=$post['class1st'];
				$recordSet['class2nd']=$post['class2nd'];
				$recordSet['diagnosis_code']=$post['code'];
				$recordSet['method']=$post['method'];
				$recordSet['language']=$post['language'];
				/*
					作成：丁錫圓
					作成日：24/05/29
				*/
				$recordSet['mail_delay']=$post['mail_delay'];
				$recordSet['date_schedule']=$post['schedule'];
				/* ここまで */
				$situTb->saveRecordInfo($recordlWhere, $recordSet);	
				$situTb->saveApplicantInfo($applicantWhere, $applicantSet);	

			echo "
			<script>
			alert('保存しました。')
			self.location.href='/admin/situation/list';
			</script>
			";	
			}
		}

		function GetOptionDatasForInput2() {
			$optionTb = $this->getServiceLocator()->get("OptionTable");
			$beforeOptionDatas = $optionTb->ReadValid();
	
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
			/*
			作成：丁錫圓
			作成日：24/05/29
			*/
			// sw's edit code 240529 -> compare optionDatas and redordArray
			foreach ($class2ndDatas as $data) {
				$class_upper = $data["class_upper"];
				
				// Check if class_upper exists in $class1stDatas
				if (!isset($class1stDatas[$class_upper])) {
					// "Notice: Undefined index $class_upper in \$class1stDatas\n";
					continue; // Skip this iteration if the index is not set
				}
			
				$class1stValue = $class1stDatas[$class_upper];
			
				// Check if $class1stValue is set in $afterOptionDatas["class2nd"]
				if (!isset($afterOptionDatas["class2nd"][$class1stValue])) {
					$afterOptionDatas["class2nd"][$class1stValue] = array();
				}
			
				array_push($afterOptionDatas["class2nd"][$class1stValue], $data);
			}
			/* ここまで */
		
			return $afterOptionDatas;
		}	

	/** Send Result Mail to Applicant by PIC Admin 
	 * @param array $applicantData
	 * @param array $recordData
	 * @param array $adminData
	 * @return string "success" or "fale"
	*/
	function SendResultMailToApplicantByPICAdmin($applicantData, $recordData, $adminData) {
		$mail = new MailRequest();

		// load basic setting for MailSender
		$param["config"] = $this->getConfig();

		$param["title"] = "ITスキル診断結果のお知らせ（ジエンジサービス）";
		
		$caseText = "新卒";
		if ($recordData["case"] == 1) { $caseText = "中途"; }
		$param["content"] = "{{applicant_name}}様\n"
											. "お世話になっております。\n"
											.	"株式会社ジエンジサービス　ITスキル診断担当です。\n"
											. "\n"
											. "株式会社ジエンジサービスのITスキル診断担当者でございます。\n"
											. "ITスキル診断結果が出ましたので、お知らせさせて頂きます。\n"
											. "診断内容についてご確認をお願いいたします。\n"
											. "\n"
											. "＜申請者情報＞\n"
											. "申請者：{{applicant_name}}（{{kana}}）\n"
											. "応募区分：{{case}}\n"
											. "学　　歴：{{education}}\n"
											. "専　　攻：{{major}}\n"
											. "試 験 日：{{execute_date}}\n"
											. "\n"
											. "得　　点：{{get_point}}/100点\n"
											. "評　　価：{{rank}}/（A~F）\n"
											. "診断評価：{{diagnosis_comment}}\n"
											. "\n"
											. "※ITスキル診断に不明点などございましたら下記の宛先まで\n"
											. "　お問い合わせください。\n"
											. "\n"
											. "＜問い合わせ先＞\n"
											. "担当者：{{admin_name}}\n"
											. "連絡先：{{admin_id}}\n"
											. "\n"
											. "以上、よろしくお願いいたします。\n"
											. "※このメールに返信しないでください。";
		$param["content"] = str_replace("{{applicant_name}}", $applicantData["name"], $param["content"]);
		$param["content"] = str_replace("{{kana}}", $applicantData["kana"], $param["content"]);
		$param["content"] = str_replace("{{case}}", $caseText, $param["content"]);
		$param["content"] = str_replace("{{education}}", $recordData["education"], $param["content"]);
		$param["content"] = str_replace("{{major}}", $recordData["major"], $param["content"]);
		$param["content"] = str_replace("{{execute_date}}", $recordData["execute_date"], $param["content"]);
		$param["content"] = str_replace("{{get_point}}", $recordData["get_point"], $param["content"]);
		$param["content"] = str_replace("{{rank}}", $recordData["rank"], $param["content"]);
		$param["content"] = str_replace("{{diagnosis_comment}}", $recordData["diagnosis_comment"], $param["content"]);
		$param["content"] = str_replace("{{admin_name}}", $adminData["name"], $param["content"]);
		$param["content"] = str_replace("{{admin_id}}", $adminData["id"], $param["content"]);

		$param["managerEmail"] = $adminData["id"];
		$param["email"] = $applicantData["email"];;
		$param["password"] = $adminData["password"];
		$param["name"] = $adminData["name"];
		$param["smtp_password"] = $adminData["smtp_password"];

		$result = $mail->mailsender($param);
		// $result = $this->getServiceLocator()->get("mailsender");
		if (isset($result["exception"])) {
			$this->SaveLog($result["exception"]);
			return "exception";
		}

		$result_row = $result["transport"]->getConnection()->getResponse();

		$results = str_replace("\r", "", str_replace("\n", "", str_replace(" ", "", $result_row[0])));
		switch(substr(strtolower($results), 0, 5)) {
			case "250ok":
				$status = "success"; break;
			default:
				$status = "fale"; break;
		}

		return $status;
  }

	/** save log in public/log.txt
	 * @param string $log
	 * @return string $log
	 */
	function SaveLog($log) {
		$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];
		$datetime = date("Y-m-d H:i:s");

		$fp = fopen($DOCUMENT_ROOT . "/log.txt", "a");
		fwrite($fp, $datetime . "\n" . $log . "\n");
		fclose($fp);

		return $log;
	}
}