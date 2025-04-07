<?php
namespace Admin\Controller;
require 'vendor/autoload.php';
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Admin\Model\MailRequest;
use Admin\Model\LogModule;
use Dompdf\Dompdf;
use Dompdf\Options;

use DOMPDFModule\View\Model\PdfModel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
		$datas = $this->GetOptionDatas($datas);

		$LogModule = new LogModule();
		$recordTb = $this->getServiceLocator()->get("RecordTable-Admin");

		try {
			$datas["totalApply"] = $recordTb->CountApplyData();
			$datas["totalRequest"] = $recordTb->CountRequestData();
			$datas["countOver"] = $recordTb->CountOverData();
		} catch (\Exception $e) {
			$logData["reason"] = "exception at SituationController listAction RecordTable Count";
			$logData["message"] = $e->getMessage();
			$log = $LogModule->SaveLog($logData);
			print_r($log);
			exit;
		}
		
		$query  = $this->params()->fromQuery();
		$page = 1;
		if (isset($query["page"])) { $page = $query["page"]; }

		$sqlWhere = array();
		if (isset($query["name"])) { $sqlWhere["name"] = $query["name"]; }
		if (isset($query["date"])) { $sqlWhere["date_schedule"] = $query["date"]; }

		if (isset($query["select"])) {
			$selectData = explode("-", $query["select"]);

			switch ($selectData[0]) {
				case "status":
					switch ($selectData[1]) {
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
							$sqlWhere["rank"] = "not-F";
							break;
						case "unexecute":
							$sqlWhere["request_date"] = "not null";
							$sqlWhere["execute_date"] = "not null";
							$sqlWhere["rank"] = "F";
							break;
					}
					break;
				case "education":
					switch ($selectData[1]) {
						case "high":
							$sqlWhere["education"] = "高卒"; break;
						case "voca":
							$sqlWhere["education"] = "専門卒"; break;
						case "uni":
							$sqlWhere["education"] = "大卒"; break;
						case "grad":
							$sqlWhere["education"] = "大学院卒"; break;
					}
					break;
				default:
					$sqlWhere[$selectData[0]] = $selectData[1];
					break;
			}
		}

		$recordPaginator = "";
		if (empty($sqlWhere)) {
			try {
				$recordPaginator = $recordTb->GetAllList();
			} catch (\Exception $e) {
				$logData["reason"] = "exception at SituationController listAction RecordTable GetAllList";
				$logData["message"] = $e->getMessage();
				$log = $LogModule->SaveLog($logData);
				print_r($log);
				exit;
			}
		} else {
			try {
				$recordPaginator = $recordTb->GetListBySearch($sqlWhere);
			} catch (\Exception $e) {
				$logData["reason"] = "exception at QuestionController listAction RecordTable GetListBySearch";
				$logData["message"] = $e->getMessage();
				$log = $LogModule->SaveLog($logData);
				print_r($log);
				exit;
			}
		}
		$recordPaginator->setCurrentPageNumber($page);
		$recordPaginator->setItemCountPerPage(10);
		$datas["recordPaginator"] = $recordPaginator;

		return $this->SetViewModel($datas, "/situation/situation_list.phtml");
	}

	/** When you choose list data on 診断者一覧 page 朴昰成 */
	public function detailAction() {
		$this->ChkLogin();
		$datas["breadcrumbData"] = ["ITスキル診断状況管理", "診断状況詳細"];
		
		$datas = $this->GetOptionDatas($datas);
		$idx = $this->params()->fromRoute("index");
		$datas['idx'] =$idx;
		$LogModule = new LogModule();
		$recordTb = $this->getServiceLocator()->get("RecordTable-Admin");
		$recordData = "";
		try {
			$recordData = $recordTb->ReadByIdx($idx);
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
		$diagnosisData = "";
		try {
			$diagnosisData = $diagnosisTb->ReadForRecordByCodenDate($recordData["diagnosis_code"], $recordData["diagnosis_date"]);
		} catch (\Exception $e) {
			$logData["reason"] = "exception at SituationController detailAction ApplicantTable ReadForRecordByCodenDate";
			$logData["message"] = $e->getMessage();
			$log = $LogModule->SaveLog($logData);
			die($log);
		}
		$datas["diagnosisData"] = $diagnosisData;

		$questionTb = $this->getServiceLocator()->get("QuestionTable");
		$questionIdxDatas = explode(",", $diagnosisData["question_idxs"]);
		$questionDatas = array();
		foreach ($questionIdxDatas as $index => $questionIdx) {
			$questionData = "";
			try {
				$questionData = $questionTb->ReadByIdx($questionIdx);
			} catch (\Exception $e) {
				$logData["reason"] = "exception at SituationController detailActio QuestionTable ReadByIdx foreach: " . $index;
				$logData["message"] = $e->getMessage();
				$log = $LogModule->SaveLog($logData);
				die($log);
			}

			$questionDatas[] = $questionData;
		}
		$datas["questionDatas"] = $questionDatas;

		/* ここまで */
		return $this->SetViewModel($datas, "/situation/situation_detail.phtml");
	}

	/** When you click 新規登録 button on 診断者一覧 page */
	function requestAction() {
		$idxs = $this->params()->fromPost("idxs");
		$recordIdxs = explode(",", $idxs);

		$LogModule = new LogModule();
		$applicantTb = $this->getServiceLocator()->get("ApplicantTable-Admin");
		$recordTb = $this->getServiceLocator()->get("RecordTable-Admin");
		$adminTb = $this->getServiceLocator()->get("AdminTable");

		$recordDatas = array();
		$errorRecordDatas = array();
		foreach ($recordIdxs as $idx) {
			try {
				$recordData = $recordTb->ReadByIdx($idx);
			} catch (\Exception $e) {
				$logData["reason"] = "exception at SituationController requestAction RecordTable ReadByIdx";
				$logData["message"] = $e->getMessage();
				$log = $LogModule->SaveLog($logData);
				die($log);
			}

			if ($recordData["request_date"] != null) { $errorRecordDatas[] = $recordData; }
			$recordDatas[] = $recordData;
		}

		if ($errorRecordDatas) {
			die(json_encode($errorRecordDatas));
		}

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

		die("success");
	}

	/** When Send Mail for Notice Result */
	public function mailAction() {
		$idxs = $this->params()->fromPost("idxs");
		$idxDatas = explode(",", $idxs);

		$LogModule = new LogModule();
		$recordTb = $this->getServiceLocator()->get("RecordTable-Admin");

		$recordDatas = array();
		$recordIdxDatas = array();
		foreach ($idxDatas as $idx) {
			$recordData = "";
			try {
				$recordData = $recordTb->ReadByIdx($idx);
			} catch (\Exception $e) {
				$logData["reason"] = "exception at SituationController mailAction RecordTable ReadByIdx";
				$logData["message"] = $e->getMessage();
				$log = $LogModule->SaveLog($logData);
				die($log);
			}

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
				try {
					$applicantDatas[] = $applicantTb->ReadByIdx($recordData["applicant_idx"]);
				} catch (\Exception $e) {
					$logData["reason"] = "exception at SituationController mailAction ApplicantTable ReadByIdx";
					$logData["message"] = $e->getMessage();
					$log = $LogModule->SaveLog($logData);
					die($log);
				}
			}

			die(json_encode($applicantDatas));
		}

		// read pic_admin data
		$adminTb = $this->getServiceLocator()->get("AdminTable");
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

			// 수신자 이메일과 이름 설정
			$param['managerEmail']=$managerInfo["id"];
			$param['email']=$recentPassword["email"];;
			$param['password']= $managerInfo["password"];
			$param['name']= $managerInfo["name"];
			$param['smtp_password']=$managerInfo["smtp_password"];


			$param['title']="ITスキル診断依頼のお知らせ（ジエンジサービス）";
			$param["content"] = "{{applicant_name}}様\n"
												. "お世話になっております。\n\n"
												. "ITスキル診断についてお知らせさせていただきます。\n"
												. "以下URLより「ITスキル診断サイト」にログインし診断を行ってください。\n\n"
												. "ログインID ：{{login_id}}\n"
												. "ログインPWD：{{login_password}}\n\n"
												. "＜ITスキル診断URL＞\n"
												. "{{url}}/login\n\n"
												. "※ITスキル診断が可能な有効期限は{{dateSchedule}}分 ~ {{dateSchduleEnd}}です。\n"
												. "   有効期限内に受験を受けない場合、自動的に失格となりますのでご了承ください。\n\n"
												. "※ITスキル診断に不明点などございましたら下記の宛先まで\n"
												. "   お問い合わせください。\n\n"
												. "＜問い合わせ先＞\n"
												. "担当者：ITスキル診断担当\n"
												. "連絡先：{{manager_email}}\n\n"
												. "以上、よろしくお願いいたします。\n"
												. "※このメールに返信しないでください。";
		/* ここまで */
			// print_r($param['title']);
			// $param['content']=str_replace("{{user_name}}","変換する試験受け者名",$param['content']);
			$param["content"] = str_replace("{{applicant_name}}", $recentPassword["name"], $param["content"]);
			$param["content"] = str_replace("{{login_id}}", $recentPassword["email"], $param["content"]);
			$param["content"] = str_replace("{{login_password}}", $recentPassword["password"], $param["content"]);
			$param["content"] = str_replace("{{url}}", $param['config']['user-url']['applicant'], $param["content"]);
			$param["content"] = str_replace("{{dateSchedule}}", $applicantInfo["date_schedule"], $param["content"]);
			$param["content"] = str_replace("{{dateSchduleEnd}}", $dateSchduleEnd, $param["content"]);
			$param["content"] = str_replace("{{manager_email}}", $param['managerEmail'], $param["content"]);


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
					修正日：24/06/19
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
					alert('依頼しました。');
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
	
				/*
					作成：丁錫圓
					修正：朴昰成
					修正日：24/06/19
				*/

				/* 修正前：
				echo "
				<script>
				alert('依頼が完了しました。')
				self.location.href='/admin/situation/list';
				</script>
				";	
				*/

				/* 修正後： */
				echo "
				<script>
					alert('依頼しました。');
					self.location.href='/admin/situation/list';
				</script>
				";
				/* ここまで */
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

		public function getdiagnosisdataAction(){
			$post = $this->params()->fromPost();
			$situTb = $this->getServiceLocator()->get("situTable");
			if (isset($post["class2nd"]) && isset($post['level'])) {
				$result = $situTb->ReadDiagnosis($post["class2nd"], $post["level"]);
				die(json_encode($result));
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
	/* temp */

	public function diagnosisAction() {
		$index = $this->params()->fromRoute("index");
		$datas["breadcrumbData"] = ["ITスキル診断状況管理", "問題確認"];

		$recordTb = $this->getServiceLocator()->get("RecordTable-Admin");
		$diagnosisTb = $this->getServiceLocator()->get("DiagnosisTable-Admin");
		$questionTb = $this->getServiceLocator()->get("QuestionTable");

		$recordData = $recordTb->ReadByIdx($index);
		$diagnosisData = $diagnosisTb->ReadByCode($recordData["diagnosis_code"]);
		$answerDatas = explode(",", $recordData["answer_data"]);
		$questionIdxDatas = explode(",", $diagnosisData["question_idxs"]);
		$optionTb = $this->getServiceLocator()->get("OptionTable");

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
			$tableData["class1st"] = $optionTb->ReadByIdx($questionData["class1st"])["text"];
			$tableData["class2nd"] = $optionTb->ReadByIdx($questionData["class2nd"])["text"];
			$tableData["correctChar"] = $correctChar;

			$tableDatas[] = $tableData;
		}

		$datas["tableDatas"] = $tableDatas;

		return $this->SetViewModel($datas, "/situation/situation_diagnosis.phtml");
	}
	/* temp */

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
		print_r("Asd");
		print_r(__DIR__);
		exit;
		
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
	
}