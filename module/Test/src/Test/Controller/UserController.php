<?php

namespace Test\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\Controller\Plugin\Redirect;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Session\Container;

class UserController extends AbstractActionController
{
  public function mainAction()
  {
    $this->layout("layout/none");

    //main==============================================================
    $post = $this->params()->fromPost();

    if (isset($post["url"])) {
      $examTb = $this->getServiceLocator()->get("ExamTable");
      if ($examTb->readByUrl($post["url"]) != null) {
        die("success");
      }
      die("fail");
    }

    //view==============================================================
    $vm = new ViewModel();
    $vm->setTemplate("/user/main.phtml");
    return $vm;
  }

  public function loginAction()
  {
    $this->layout("layout/none");

    //main==============================================================
    $url = $this->params()->fromRoute()["url"];

    $examTb = $this->getServiceLocator()->get("ExamTable");
    $examData = $examTb->readByUrl($url);

    if ($examData == null) {
      echo "
      <script>
        alert('URLを確認してください');
        self.location.href='/user/main';
      </script>
      ";
      exit;
    }

    if ($examData["get_point"] != null) {
      $message[0] = "該当試験は受け済みの試験になります。";
      $message[1] = "ご協力ありがとうございました。";
      $vm = new ViewModel(["message" => $message]);
      $vm->setTemplate("/user/alert");
      return $vm;
    }

    $post = $this->params()->fromPost();

    if (isset($post["id"])) {
      die($examTb->login($url, $post["id"], $post["password"]));
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
		$post = $this->params()->fromPost();
    $url = $this->params()->fromRoute()["url"];

    $examTb = $this->getServiceLocator()->get("ExamTable");
    $examData = $examTb->readByUrl($url);

		if ($examData["question_data"] == "") {		// 問題がいない場合
			if (isset($post["academic"])) { $this->MakeExam($url, $post); }		// 応募者が情報を確認した時
			else { return $this->Setting($examData); }		// 問題設定ページに移動
		}

		echo "here";
		exit;

		if (isset($post["ready"])) {		// 試験準備画面に移動
			return $this->SetViewModel($examData , "/user/exam.phtml");
		}
		/* ここまで */
  }

	/* 問題がいない場合の機能追加
		作成：朴昰成
		作成日：2024/03/04
	*/

	public function Setting($examData) {		// 問題設定ページに移動
		$datas["examData"] = $examData;

    $typeTb = $this->getServiceLocator()->get("QuestionTypeTable");
		$datas["typeDatas"] = $typeTb->readAll();
		
		return $this->SetViewModel($datas, "user/announce.phtml");
	}

	/** Make Exam */
	public function MakeExam($url, $post) {		// 試験の問題を登録
		if (isset($post["major"])) { $post["academic"] += 1; }

		$questionTb = $this->getServiceLocator()->get("QuestionPoolTable");
    $examTb = $this->getServiceLocator()->get("ExamTable");
		
		$post["num"] = 5;	//temp
		$questionDatas = iterator_to_array($questionTb->ReadRandForExam($post));
		
		$question_data = "";
		foreach ($questionDatas as $data) {		// 選択した問題のidxを保存
			$question_data .= $data["idx"] . ",";
		}
		$post["question_data"] = substr($question_data , 0, -1);

		$examTb->updateExamSetting($url, $post);
	}

	public function SetViewModel($datas, $template) {
		$vm = new ViewModel($datas);
		$vm->setTerminal(true);		// 本当レイアウト未設定
		$vm->setTemplate($template);
		return $vm;
	}
	 /* ここまで */
}
