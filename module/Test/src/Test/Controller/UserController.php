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
  }
}
