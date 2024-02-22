<?php

namespace Test\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\Controller\Plugin\Redirect;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Session\Container;

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

  public function questionAction()
  {
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
      return $this->questionList($query["page"]);
    }
  }

  public function examAction()
  {
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
      return $this->examList($query["page"]);
    }
  }

  public function questionRegister()
  {
    $post = $this->params()->fromPost();

    if (isset($post["type"])) {
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

    $breadcrumb[0] = "問題管理";
    $breadcrumb[1] = "問題登録";
    $this->layout()->breadcrumb = json_encode($breadcrumb);

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

    $breadcrumb[0] = "問題管理";
    $breadcrumb[1] = "問題登録";
    $breadcrumb[2] = "確認";
    $this->layout()->breadcrumb = json_encode($breadcrumb);

    //view==============================================================
    $vm = new ViewModel();
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
    $datas["totalPage"] = intval($questionTb->readCount() / 10 + 1);
    $datas["cPage"] = $page;

    $breadcrumb[0] = "問題管理";
    $breadcrumb[1] = "問題一覧";
    $this->layout()->breadcrumb = json_encode($breadcrumb);

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

    $breadcrumb[0] = "問題管理";
    $breadcrumb[1] = "問題一覧";
    $breadcrumb[2] = "問題詳細";
    $this->layout()->breadcrumb = json_encode($breadcrumb);

    //view==============================================================
    $vm = new ViewModel($datas);
    $vm->setTemplate("/admin/question/detail.phtml");
    return $vm;
  }

  public function examRegister()
  {
    $post = $this->params()->fromPost();

    if (isset($post["type"])) {
      $session = new Container("exam");

      foreach ($post as $key => $data) {
        $session->offsetSet($key, $data);
      }

      header("Location: ./register/confirm");
      exit;
    }

    $typeTb = $this->getServiceLocator()->get("QuestionTypeTable");
    $datas["types"] = $typeTb->readAll();

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

    $breadcrumb[0] = "応募者状況管理";
    $breadcrumb[1] = "試験登録";
    $this->layout()->breadcrumb = json_encode($breadcrumb);

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

      echo "
      <script>
        alert('登録しました。');
        self.location.href='../list?page=1'
      </script>
      ";
    }

    $session = new Container("exam");
    $questionTb = $this->getServiceLocator()->get("QuestionPoolTable");
    $typeTb = $this->getServiceLocator()->get("QuestionTypeTable");

    /* 機能変更
      作成：朴夏成
      修正：朴夏成
      修正日：2024/02/21
    */

    /* 修正前：
    $tempDatas = $questionTb->ReadRandByTypenLevelnNum($session->offsetGet("type"), $session->offsetGet("level"), $session->offsetGet("num"));
    */

    /* 修正後： */
    $tempDatas = $questionTb->ReadRandForExam($session->offsetGet("type"), $session->offsetGet("academic"), $session->offsetGet("career"), $session->offsetGet("certificate"), $session->offsetGet("num"));
    /* ここまで */

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

    $breadcrumb[0] = "応募者状況管理";
    $breadcrumb[1] = "試験登録";
    $breadcrumb[2] = "確認";
    $this->layout()->breadcrumb = json_encode($breadcrumb);

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
    $datas["totalPage"] = intval($examTb->readCount() / 10 + 1);
    $datas["cPage"] = $page;

    $breadcrumb[0] = "応募者状況管理";
    $breadcrumb[1] = "試験一覧";
    $this->layout()->breadcrumb = json_encode($breadcrumb);

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

    //view==============================================================
    $vm = new ViewModel($datas);
    $vm->setTemplate("/admin/exam/detail.phtml");
    return $vm;
  }
}
