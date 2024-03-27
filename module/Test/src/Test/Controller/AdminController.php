<?php

namespace Test\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\Controller\Plugin\Redirect;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Session\Container;

use Test\Model\MailSender;

class AdminController extends AbstractActionController
{
  public function loginAction()
  {
    $this->layout("layout/none");

    $post = $this->params()->fromPost();

    if (isset($post["id"])) {
      $session = new Container("user");
      $session->offsetSet("id", $post["id"]);
      $adminTb = $this->getServiceLocator()->get("AdminInfoTable");
      die($adminTb->login($post["id"], $post["password"]));
    }

    //view==============================================================
    $vm = new ViewModel();
    $vm->setTemplate("/admin/login.phtml");
    return $vm;
  }

	/* 機能の追加
		作成：朴昰成
		作成日：2024/03/13
	*/
  public function logoutAction() {
		$session = new Container("user");
		unset($session["id"]);
		echo "<script>
			alert('ログアウトしました。');
			self.location.href='/admin/login';
		</script>";
	}
	/* ここまで */

  public function questionAction()
  {
		$submenuArray = array(
			"問題一覧" => ["list", "detail"],
			"問題登録" => ["register", "confirm"],
		);
		$this->layout("admin");
		$this->layout()->submenuArray = json_encode($submenuArray);
		
    $route = $this->params()->fromRoute();
    $query = $this->params()->fromQuery();

    if ($route["cat"] == "register") {
      $this->layout()->category = 4;
      if (isset($route["status"]) && $route["status"] == "confirm") {
        return $this->questionConfirm();
      }
      return $this->questionRegister();
    }

    if ($route["cat"] == "list") {
      $this->layout()->category = 3;
      if (isset($route["status"]) && $route["status"] == "detail") {
        if (isset($route["index"])) {
          return $this->questionDetail($route["index"]);
        }
        die();
      }
			
			$page = 1;
			if (isset($query) && isset($query["page"])) { $page = $query["page"]; }
      return $this->questionList($page);
    }
  }

  public function examAction()
  {
		$submenuArray = array(
			"試験一覧" => ["list", "detail"],
			"試験登録" => ["register", "confirm"],
		);
		$this->layout("admin");
		$this->layout()->submenuArray = json_encode($submenuArray);

    $route = $this->params()->fromRoute();
    $query = $this->params()->fromQuery();

    if ($route["cat"] == "register") {
      $this->layout()->category = 2;
      if (isset($route["status"]) && $route["status"] == "confirm") {
        return $this->examConfirm();
      }
      return $this->examRegister();
    }

    if ($route["cat"] == "list") {
      $this->layout()->category = 1;
      if (isset($route["status"]) && $route["status"] == "detail") {
        if (isset($route["index"])) {
          return $this->examDetail($route["index"]);
        }
        die();
      }

			$page = 1;
			if (isset($query) && isset($query["page"])) { $page = $query["page"]; }
      return $this->examList($page);
    }
  }

  public function questionRegister()
  {
    $post = $this->params()->fromPost();

    if (isset($post["type"])) {
			/* 機能の追加
				作成：朴昰成
				作成日：2024/03/08
			*/
			$this->ClearSession();
			/* ここまで */
      $session = new Container("question");

      foreach ($post as $key => $data) {
        $session->offsetSet($key, $data);
      }

      header("Location: ./register/confirm");
      exit;
    }

    $typeTb = $this->getServiceLocator()->get("QuestionTypeTable");
    $typeDatas = $typeTb->readAll();

    $datas["typeDatas"] = $typeDatas;

		/* コードデバッグ
			作成：朴昰成
			修正：朴昰成
			修正日：2027/03/08
		*/

		/* 修正前
    $breadcrumb[0] = "問題管理";
    $breadcrumb[1] = "問題登録";
    $this->layout()->breadcrumb = json_encode($breadcrumb);
		*/

		/* 修正後 */
		$breadcrumb = array("問題管理", "問題登録");
		$datas["breadcrumbData"] = $breadcrumb;
		/* ここまで */

    //view==============================================================
    $vm = new ViewModel($datas);
    $vm->setTemplate("/admin/question/register.phtml");
    return $vm;
  }

  public function questionConfirm()
  {
    $post = $this->params()->fromPost();

    if (isset($post["type"])) {
      $questionTb = $this->getServiceLocator()->get("QuestionPoolTable");
      $questionTb->createQuestion($post);

      echo "
      <script>
        alert('登録しました。');
        self.location.href='../list?page=1';
      </script>
      ";
    }

		/* コードデバッグ
			作成：朴昰成
			修正：朴昰成
			修正日：2027/03/08
		*/

		/* 修正前
    $breadcrumb[0] = "問題管理";
    $breadcrumb[1] = "問題登録";
    $breadcrumb[2] = "確認";
    $this->layout()->breadcrumb = json_encode($breadcrumb);
		*/

		/* 修正後 */
		$breadcrumb = array("問題管理", "問題登録", "確認");
		$datas["breadcrumbData"] = $breadcrumb;
		/* ここまで */

    //view==============================================================
    $vm = new ViewModel($datas);
    $vm->setTemplate("/admin/question/confirm.phtml");
    return $vm;
  }

  public function questionList($page)
  {
    $questionTb = $this->getServiceLocator()->get("QuestionPoolTable");
    $typeTb = $this->getServiceLocator()->get("QuestionTypeTable");

    $tempDatas = $questionTb->readTenByPage($page);

    $questionDatas = [];
    $typeTitles = [];
    foreach ($tempDatas as $index => $data) {
      $questionDatas[$index] = $data;
      $typeData = $typeTb->readByType($data["question_type"]);
      $typeTitles[$index] = $typeData["question_title"];
    }

    $datas["questionDatas"] = $questionDatas;
    $datas["typeTitles"] = $typeTitles;
    $datas["dataIndex"] = $questionTb->readCount() - (($page - 1) * 10);
		/* 機能の変更
			作成：朴昰成
			修正：朴昰成
			修正日：2027/03/12
		*/

		/* 修正前
    $datas["totalPage"] = intval($questionTb->readCount() / 10 + 1);
    $datas["cPage"] = $page;
		*/

		/* 修正後 */

		$paginationData["totalPage"] = intval($questionTb->readCount() / 10 + 1);
		$paginationData["currentPage"] = $page;
		$paginationData["url"] = "list?page=";
		$datas["paginationData"] = $paginationData;
		/* ここまで */

		/* コードデバッグ
			作成：朴昰成
			修正：朴昰成
			修正日：2027/03/12
		*/

		/* 修正前
    $breadcrumb[0] = "問題管理";
    $breadcrumb[1] = "問題一覧";
    $this->layout()->breadcrumb = json_encode($breadcrumb);
		*/

		/* 修正後 */
		$breadcrumb = array("問題管理", "問題一覧");
		$datas["breadcrumbData"] = $breadcrumb;
		/* ここまで */

    //view==============================================================
    $vm = new ViewModel($datas);
    $vm->setTemplate("/admin/question/list.phtml");
    return $vm;
  }

  public function questionDetail($idx)
  {
    $questionTb = $this->getServiceLocator()->get("QuestionPoolTable");
    $questionData = $questionTb->readByIdx($idx);
    $datas["questionData"] = $questionData;

    $typeTb = $this->getServiceLocator()->get("QuestionTypeTable");
    $datas["typeData"] = $typeTb->readByType($questionData["question_type"]);

		/* ここまで */

		/* コードデバッグ
			作成：朴昰成
			修正：朴昰成
			修正日：2027/03/12
		*/

		/* 修正前
    $breadcrumb[0] = "問題管理";
    $breadcrumb[1] = "問題一覧";
    $breadcrumb[2] = "問題詳細";
    $this->layout()->breadcrumb = json_encode($breadcrumb);
		*/

		/* 修正後 */
		$breadcrumb = array("問題管理", "問題一覧", "問題詳細");
		$datas["breadcrumbData"] = $breadcrumb;
		/* ここまで */

    //view==============================================================
    $vm = new ViewModel($datas);
    $vm->setTemplate("/admin/question/detail.phtml");
    return $vm;
  }

  public function examRegister()
  {
    $post = $this->params()->fromPost();

		/* 機能変更と追加
			作成：朴夏成
			修正：朴夏成
			修正日：2024/02/27
		*/
		
		/* 修正前：
    if (isset($post["type"])) {
      $session = new Container("exam");
		*/

		/* 修正後： */
		if (isset($post["name"])) {
			$this->ClearSession();
			
			$session = new Container("exam");
		/* ここまで */

      foreach ($post as $key => $data) {
        $session->offsetSet($key, $data);
      }

      header("Location: ./register/confirm");
      exit;
    }

    $typeTb = $this->getServiceLocator()->get("QuestionTypeTable");
    $datas["types"] = $typeTb->readAll();

		/* 機能の削除
			作成：朴夏成
			削除：朴夏成
			削除日：2024/03/01

		削除前：
    $rndId = "";
    for ($i = 0; $i < 10; $i++) {
      switch (rand(0, 1)) {
        case 0:
          $rndId = $rndId . (string)rand(0, 9);
          break;
        case 1:
          $rndId = $rndId . chr(rand(65, 90));
          break;
      }
    }
    $datas["rndId"] = $rndId;
		ここまで */

    $rndPassword = "";
    for ($i = 0; $i < 8; $i++) {
      switch (rand(0, 2)) {
        case 0:
          $rndPassword = $rndPassword . (string)rand(0, 9);
          break;
        case 1:
          $rndPassword = $rndPassword . chr(rand(65, 90));
          break;
        case 2:
          $rndPassword = $rndPassword . chr(rand(97, 122));
          break;
      }
    }
    $datas["rndPassword"] = $rndPassword;

		/* コードデバッグ
			作成：朴昰成
			修正：朴昰成
			修正日：2027/02/26
		*/

		/* 修正前
    $breadcrumb[0] = "応募者状況管理";
    $breadcrumb[1] = "試験登録";
    $this->layout()->breadcrumb = json_encode($breadcrumb);
		*/

		/* 修正後 */
		$breadcrumb = array("応募者状況管理", "試験登録");
		$datas["breadcrumbData"] = $breadcrumb;
		/* ここまで */

    //view==============================================================
    $vm = new ViewModel($datas);
    $vm->setTemplate("/admin/exam/register.phtml");
    return $vm;
  }

  public function examConfirm()
  {
    $post = $this->params()->fromPost();
    $examTb = $this->getServiceLocator()->get("ExamTable");

    if (isset($post["url"])) {
      $examTb->createExam($post);

			/* 機能の変更
				作成：朴昰成
				修正：朴昰成
				修正日：2024/03/27
			*/

			/* 修正前：
      echo "
      <script>
        alert('登録しました。');
        self.location.href='../list?page=1'
      </script>
      ";
			*/

			/* 修正後： */
			$examIdx = $examTb->readByUrl($post["url"])["idx"];

      echo "
      <script>
        alert('登録しました。');
        self.location.href='../list/detail/$examIdx'
      </script>
      ";
			/* ここまで */
    }

		/* 機能の変更と改善
			作成：朴夏成
			修正：朴夏成
			修正日：2024/02/21
		*/

		/* 修正前：
    $session = new Container("exam");

    $questionTb = $this->getServiceLocator()->get("QuestionPoolTable");
    $typeTb = $this->getServiceLocator()->get("QuestionTypeTable");

    $tempDatas = $questionTb->ReadRandByTypenLevelnNum($session->offsetGet("type"), $session->offsetGet("level"), $session->offsetGet("num"));

    $questionDatas = [];
    $typeTitles = [];
    $question_data = "";
    foreach ($tempDatas as $index => $data) {
      $questionDatas[$index] = $data;
      $typeTitles[$index] = $typeTb->readByType($data["question_type"])["question_title"];
      $question_data = $question_data . $data["idx"] . ",";
    }
    $question_data = substr($question_data, 0, -1);
    $datas["questionDatas"] = $questionDatas;
    $datas["typeTitles"] = $typeTitles;
    $datas["question_data"] = $question_data;
		*/

		/* 修正後： */
		$session = new Container("exam");

		if ($session->offsetGet("setQuestion") == "true") {
			$questionTb = $this->getServiceLocator()->get("QuestionPoolTable");
			$typeTb = $this->getServiceLocator()->get("QuestionTypeTable");

			$questionValueDatas = array (
				"type" => $session->offsetGet("type"),
				"academic" => $session->offsetGet("academic"),
				"career" => $session->offsetGet("career"),
				"certificates" => $session->offsetGet("certificates"),
				"num" => $session->offsetGet("num"),
			);

			if ($session->offsetExists("major")) { $questionValueDatas["academic"] += 1; }

			$questionDatas = iterator_to_array($questionTb->ReadRandForExam($questionValueDatas));
			$datas["questionDatas"] = $questionDatas;
		
			$typeTitles = [];
			$question_data = "";
			foreach ($questionDatas as $index => $data) {
				$typeTitles[$index] = $typeTb->readByType($data["question_type"])["question_title"];
				$question_data = $question_data . $data["idx"] . ",";
			}
			$datas["typeTitles"] = $typeTitles;

			$question_data = substr($question_data, 0, -1);
			$datas["question_data"] = $question_data;
		}
		/* ここまで */

    $rndUrl = "";
    do {
      $rndUrl = "";
      for ($i = 0; $i < 10; $i++) {
        switch (rand(0, 2)) {
          case 0:
            $rndUrl = $rndUrl . (string)rand(0, 9);
            break;
          case 1:
            $rndUrl = $rndUrl . chr(rand(65, 90));
            break;
          case 2:
            $rndUrl = $rndUrl . chr(rand(97, 122));
            break;
        }
      }
    } while (!empty($examTb->readByUrl($rndUrl)));
    $datas["rndUrl"] = $rndUrl;

		/* コードデバッグ
			作成：朴昰成
			修正：朴昰成
			修正日：2027/02/26
		*/

		/* 修正前
    $breadcrumb[0] = "応募者状況管理";
    $breadcrumb[1] = "試験登録";
    $breadcrumb[2] = "確認";
    $this->layout()->breadcrumb = json_encode($breadcrumb);
		*/

		/* 修正後 */
		$breadcrumb = array("応募者状況管理", "試験登録", "確認");
		$datas["breadcrumbData"] = $breadcrumb;
		/* ここまで */

    //view==============================================================
    $vm = new ViewModel($datas);
    $vm->setTemplate("/admin/exam/confirm.phtml");
    return $vm;
  }

  public function examList($page)
  {
    $examTb = $this->getServiceLocator()->get("examTable");

    $datas["examDatas"] = $examTb->readTenByPage($page);
    $datas["dataIndex"] = $examTb->readCount() - (($page - 1) * 10);
		/* 機能の変更
			作成：朴昰成
			修正：朴昰成
			修正日：2027/03/11
		*/

		/* 修正前
    $datas["totalPage"] = intval($examTb->readCount() / 10 + 1);
    $datas["cPage"] = $page;
		*/

		/* 修正後 */

		$paginationData["totalPage"] = intval($examTb->readCount() / 10 + 1);
		$paginationData["currentPage"] = $page;
		$paginationData["url"] = "list?page=";
		$datas["paginationData"] = $paginationData;
		/* ここまで */

		/* コードデバッグ
			作成：朴昰成
			修正：朴昰成
			修正日：2027/02/26
		*/

		/* 修正前
    $breadcrumb[0] = "応募者状況管理";
    $breadcrumb[1] = "試験一覧";
    $this->layout()->breadcrumb = json_encode($breadcrumb);
		*/

		/* 修正後 */
		$breadcrumb = array("応募者状況管理", "試験一覧");
		$datas["breadcrumbData"] = $breadcrumb;
		/* ここまで */

    //view==============================================================
    $vm = new ViewModel($datas);
    $vm->setTemplate("/admin/exam/list.phtml");
    return $vm;
  }

  public function examDetail($idx)
  {
    $examTb = $this->getServiceLocator()->get("ExamTable");
    $examData = $examTb->readByIdx($idx);
    $datas["examData"] = $examData;

		/* 機能の追加と変更
			作成：朴夏成
			修正：朴夏成
			修正日：2024/02/28
		*/

		/* 修正前：
    $questionTb = $this->getServiceLocator()->get("QuestionPoolTable");
    $typeTb = $this->getServiceLocator()->get("QuestionTypeTable");

    $questionDatas = [];
    $typeTitles = [];
    $questionIdxs = explode(",", $examData["question_data"]);
    foreach ($questionIdxs as $index => $idx) {
      $questionDatas[$index] = $questionTb->readByIdx($idx);
      $typeTitles[$index] = $typeTb->readByType($questionDatas[$index]["question_type"])["question_title"];
    }
    $datas["questionDatas"] = $questionDatas;
    $datas["typeTitles"] = $typeTitles;

    $corrects = [];
    if ($examData["get_point"] == null) {
      for ($i = 0; $i < $examData["question_nums"]; $i++) {
        $corrects[$i] = "未対応";
      }
    } else {
      $answers = explode(",", $examData["answer_data"]);
      foreach ($answers as $index => $answer) {
        if ($answer == $questionDatas[$index]["correct_answer"]) {
          $corrects[$index] = "O";
        } else {
          $corrects[$index] = "X";
        }
      }
    }
    $datas["corrects"] = $corrects;

    $breadcrumb[0] = "応募者状況管理";
    $breadcrumb[1] = "試験一覧";
    $breadcrumb[2] = "試験詳細";
    $this->layout()->breadcrumb = json_encode($breadcrumb);
		*/

		/* 修正後 */
		if ($examData["academic"] != null) {
			$questionTb = $this->getServiceLocator()->get("QuestionPoolTable");
			$typeTb = $this->getServiceLocator()->get("QuestionTypeTable");
	
			$questionDatas = [];
			$typeTitles = [];
			$questionIdxs = explode(",", $examData["question_data"]);
			foreach ($questionIdxs as $index => $idx) {
				$questionDatas[$index] = $questionTb->readByIdx($idx);
				$typeTitles[$index] = $typeTb->readByType($questionDatas[$index]["question_type"])["question_title"];
			}
			$datas["questionDatas"] = $questionDatas;
			$datas["typeTitles"] = $typeTitles;
	
			$corrects = [];
			if (is_null($examData["answer_data"])) {
				for ($i = 0; $i < $examData["question_nums"]; $i++) {
					$corrects[$i] = "未対応";
				}
			} else {
				$answers = explode(",", $examData["answer_data"]);
				foreach ($answers as $index => $answer) {
					if ($answer == $questionDatas[$index]["correct_answer"]) {
						$corrects[$index] = "O";
					} else {
						$corrects[$index] = "X";
					}
				}
			}
			$datas["corrects"] = $corrects;
		}

		$breadcrumb = array("応募者状況管理", "試験一覧", "試験詳細");
		$datas["breadcrumbData"] = $breadcrumb;
		/* ここまで */

    //view==============================================================
    $vm = new ViewModel($datas);
    $vm->setTemplate("/admin/exam/detail.phtml");
    return $vm;
  }

	/* 機能の追加
		作成：朴昰成
		作成日：2024/03/27
	*/



	/** Clear session Except user session */
	function ClearSession() {
		$session = new Container("user");

		$userSession = array();
		foreach ($session as $key => $value) {
			print_r($key . "&" . $value);
			$userSession[$key] = $value;
		}

		$session->getManager()->getStorage()->clear();
		
		$session = new Container("user");
		foreach ($userSession as $key => $value) {
			print_r($key . "&" . $value);
			$session->offsetSet($key, $value);
		}
	}

	/* ここまで */
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
}
