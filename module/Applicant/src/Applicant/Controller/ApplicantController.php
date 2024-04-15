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
	return $this->SetViewModel([] , "/applicant/application.phtml");
}

  public function mainAction()
  {
		/* 機能の変更
			作成：朴昰成
			修正：朴昰成
		*/

		/* 修正前：
    $this->layout("layout/none");
		*/

		/* 修正後： */
		$this->layout("layout/user/login");
		/* ここまで */

    //main==============================================================
    $post = $this->params()->fromPost();

    if (isset($post["url"])) {
      $examTb = $this->getServiceLocator()->get("ExamTable");
      if ($examTb->readByUrl($post["url"]) != null) {
        die("success");
      }
      die("fail");
    }

		/* 関数に変更
			作成：朴昰成
			修正：朴昰成
			修正日：2024/03/18
		*/

		/* 修正前：
    //view==============================================================
    $vm = new ViewModel();
    $vm->setTemplate("/user/main.phtml");
    return $vm;
		*/
		
		/* 修正後： */
		return $this->SetViewModel([] , "/applicant/main.phtml");
		/* ここまで */
  }

  public function loginAction()
  {
		/* 機能の変更
			作成：朴昰成
			修正：朴昰成
			修正日：2024/03/14
		*/

		/* 修正前：
    $this->layout("layout/none");
		*/

		/* 修正後： */
		$this->layout("layout/applicant/login");
		/* ここまで */

    //main==============================================================
		/* 機能の追加
			作成：朴昰成
			作成日：2024/03/15
		*/
		if (!isset($this->params()->fromRoute()["url"])) {
			$this->RedirectToMain();
			exit;
		}
		
		/* ここまで */
    $url = $this->params()->fromRoute()["url"];

    $examTb = $this->getServiceLocator()->get("ExamTable");
    $examData = $examTb->readByUrl($url);

    if ($examData == null) {
			/* 機能の変更
				作成：朴昰成
				修正：朴昰成
				作成日：2024/03/18
			*/

			/* 修正前：
      echo "
      <script>
        alert('URLを確認してください');
        self.location.href='/user/main';
      </script>
      ";
			*/

			/* 修正後： */
			$this->RedirectToMain();
			/* ここまで */
      exit;
    }

		/* 機能の削除
			作成：朴昰成
			削除：朴昰成
			削除日：2024/03/18
		*/
	
		/* 削除前：
    if ($examData["get_point"] != null) {
      $message[0] = "該当試験は受け済みの試験になります。";
      $message[1] = "ご協力ありがとうございました。";
      $vm = new ViewModel(["message" => $message]);
      $vm->setTemplate("/user/alert");
      return $vm;
    }
		ここまで */

    $post = $this->params()->fromPost();

    if (isset($post["id"])) {
			/* 機能の変更
				作成：朴昰成
				修正：朴昰成
				修正日：2024/03/14
			*/

			/* 修正前：
      die($examTb->login($url, $post["id"], $post["password"]));
			*/

			/* 修正後： */
			$result = $examTb->login($url, $post["id"], $post["password"]);
			if ($result == "success") {
				$session = new Container("user");
				$session["id"] = $post["id"];
				$session["url"] = $url;
				$session["token"] = 1;
			}
			die($result);
			/* ここまで */
    }

    //view==============================================================
    $vm = new ViewModel();
    $vm->setTemplate("/user/login.phtml");
    return $vm;
  }

  public function examAction()
  {
		/* 問題がいない場合の機能追加
			作成：朴昰成
			修正：朴昰成
			修正日：2024/03/04
		*/

		/* 修正前：
    $this->layout("layout/none");

    //main==============================================================
    $url = $this->params()->fromRoute()["url"];

    $examTb = $this->getServiceLocator()->get("ExamTable");
    $questionTb = $this->getServiceLocator()->get("QuestionPoolTable");
    $examData = $examTb->readByUrl($url);
    $datas["examData"] = $examData;

    $questionIdxs = explode(',', $examData["question_data"]);

    $post = $this->params()->fromPost();

    if (isset($post["question0"])) {
      $point = 0;
      foreach ($questionIdxs as $index => $questionIdx) {
        $questionData = $questionTb->readByIdx($questionIdx);
        if ($post["question" . $index] == $questionData["correct_answer"]) {
          $point = $point + 1;
        }
      }
      $answers = implode(",", $post);

      $examTb->updateSubmit($url, $answers, $point);

      $message[0] = "内容を送信しました。";
      $message[1] = "内容の検討後、担当者から連絡させていただきます。";
      $message[2] = "ご協力いただきありがとうございました。";
      $vm = new ViewModel(["message" => $message]);
      $vm->setTemplate("/user/alert");
      return $vm;
    }

    $questionDatas = [];
    foreach ($questionIdxs as $index => $idx) {
      $questionDatas[$index] = $questionTb->readByIdx($idx);
    }
    $datas["questionDatas"] = $questionDatas;


    //view==============================================================
    $vm = new ViewModel($datas);
    $vm->setTemplate("/user/exam.phtml");
    return $vm;
		*/

		/* 修正後： */
		$url = $this->params()->fromRoute()["url"];

		$session = new Container("user");
		if (!isset($session) || $session["url"] != $url) { $this->RedirectToLogin($url); }

		$this->layout("layout/user");

		$post = $this->params()->fromPost();

		$examTb = $this->getServiceLocator()->get("ExamTable");
		$examData = $examTb->readByUrl($url);



		if ($examData["get_point"] != "") {		// 試験をすでに受けた場合
			$message[0] = "該当試験は受け済みの試験になります。";
			$message[1] = "ご協力ありがとうございました。";
			return $this->SetViewModel(["message" => $message], "user/alert.phtml");
		}



		// 設定ができてない場合
		if ($examData["question_data"] == "") {
			//　ログインをしなかった場合
			if (!isset($session["token"])) { $this->RedirectToLogin($url); }
			unset($session["token"]);

			// 応募者が設定を確認しなかった場合、問題設定ページに移動
			if (!isset($post["academic"])) {
				$session["token"] = 1;
				return $this->Setting($examData);
			}
			
			// 応募者が設定を確認した時
			$session["token"] = 1;
			$this->MakeExam($url, $examData["question_nums"], $post);
		}


		// 試験受けるページに移動
		if (isset($post["start"])) {
			$datas["examData"] = $examData;

			// 一時的にデータアップデート
			$tempAnswers = "";
			for ($i = 0; $i < $examData["question_nums"]; $i++) {
				$tempAnswers .= "0,";
			}
			$tempAnswers = substr($tempAnswers , 0, -1);
			$examTb->updateSubmit($url, null, 0);

			$questionTb = $this->getServiceLocator()->get("QuestionPoolTable");
			$questionIdxs = explode(",", $examData["question_data"]);
			$questionDatas = array();
			foreach ($questionIdxs as $index => $idx) {
				$questionDatas[$index] = $questionTb->readByIdx($idx);
			}

			$datas["questionDatas"] = $questionDatas;

			return $this->SetViewModel($datas, "user/exam.phtml");
		}



		// 試験を受けて送信した時
		if (isset($post["end"])) {
			unset($session);
			unset($post["end"]);
			$answers = implode(",", $post);
			
			$questionTb = $this->getServiceLocator()->get("QuestionPoolTable");
			$questionIdxs = explode(",", $examData["question_data"]);
			$point = 0;
			foreach ($questionIdxs as $index => $questionIdx) {
				$questionData = $questionTb->readByIdx($questionIdx);
				if ($post["question" . $index] == $questionData["correct_answer"]) {
					$point = $point + 1;
				}
			}

			$examTb->updateSubmit($url, $answers, $point);

			$message[0] = "該当試験は受け済みの試験になります。";
			$message[1] = "内容の検討後、担当者から連絡させていただきます。";
			$message[3] = "ご協力いただきありがとうございました。";
			return $this->SetViewModel(["message" => $message], "user/alert.phtml");
		}



		//　ログインをしなかった場合
		if (!isset($session["token"])) { $this->RedirectToLogin($url); }
		unset($session["token"]);

		// 試験案内ページに移動
		return $this->SetViewModel(["name" => $examData["name"]] , "user/announce.phtml");
		/* ここまで */
  }

	/* 問題がいない場合の機能追加
		作成：朴昰成
		作成日：2024/03/04
	*/


	/** Redirect to Main page (user/main.phtml) */
	function RedirectToMain() {
		echo "
		<script>
			alert('URLを確認してください');
			self.location.href='/user/main';
		</script>
		";
		exit;
	}

	/** Redirect to Login page (user/login.phtml) */
	function RedirectToLogin($url) {
		echo "
		<script>
			alert('ログインしてください');
			self.location.href='/user/login/$url';
		</script>
		";
		exit;
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

	/** Make ViewModel with datas and template */
	function SetViewModel($datas, $template) {
		$vm = new ViewModel($datas);
		$vm->setTemplate($template);
		return $vm;
	}
	/* ここまで */
}
