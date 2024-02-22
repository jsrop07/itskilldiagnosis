<?php

namespace Test\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Sql;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Session\Container;

class QuestionPoolTable
{
  public function __construct()
  {
    //Local設定ファイルがある場合、Local設定を優先する
    if (is_file($_SERVER['DOCUMENT_ROOT'] . '/../config/autoload/local.php')) {
      $this->config = require $_SERVER['DOCUMENT_ROOT'] . '/../config/autoload/local.php';
    } else {
      $this->config = require $_SERVER['DOCUMENT_ROOT'] . '/../config/autoload/global.php';
    }
    //指定DB設定情報通り接続
    $dbArr = $this->config['db'];
    $adapter = new Adapter($dbArr);
    //Adapter設定
    $this->adapter = $adapter;
    //簡単に共通Sql宣言
    $this->sql = new Sql($this->adapter);
  }
  // 登録修正
  // 作成：丁錫圓
  // 作成日：2024/02/21
  // 修正前：
  // public function createQuestion($post)
  // {
  //   for ($i = 1; $i <= 5; $i++) {
  //     if ($post["answer" . $i] != null) {
  //       $answerArr[$i - 1] = $post["answer" . $i];
  //     }
  //   }
  //   $answers = implode("|", $answerArr);

  //   $data = array(
  //     "question_type" => $post["type"],
  //     "question_level" => $post["level"],
  //     "question" => $post["question"],
  //     "answers" => $answers,
  //     "correct_answer" => $post["correct"],
  //     "wdate" => date("Y-m-d H:i:s"),
  //   );

  //   $qry = $this->sql->insert("question_pool")->values($data);
  //   return $this->sql->prepareStatementForSqlObject($qry)->execute();
  // }
  // 修正後：
  public function createQuestion($post)
  {
    for ($i = 1; $i <= 5; $i++) {
      if ($post["answer" . $i] != null) {
        $answerArr[$i - 1] = $post["answer" . $i];
      }
    }
    $answers = implode("|", $answerArr);

    $data = array(
      "question_type" => $post["type"],
      "academic" => $post["academic"],
      "career" => $post["career"],
      "certificate" => $post["certificate"],
      "question" => $post["question"],
      "answers" => $answers,
      "correct_answer" => $post["correct"],
      "wdate" => date("Y-m-d H:i:s"),
    );

    $qry = $this->sql->insert("question_pool")->values($data);
    return $this->sql->prepareStatementForSqlObject($qry)->execute();
  }
  // ここまで

  public function readTenByPage($page)
  {
    $index = ($page - 1) * 10;
    $qry = $this->sql->select("question_pool")->order("wdate DESC")->limit(10)->offset($index);
    return $this->sql->prepareStatementForSqlObject($qry)->execute();
  }

  public function readByIdx($idx)
  {
    $qry = $this->sql->select("question_pool")->where(["idx" => $idx]);
    return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
  }

  public function readCount()
  {
    $qry = $this->sql->select("question_pool");
    return count($this->sql->prepareStatementForSqlObject($qry)->execute());
  }

	/* 機能変更
		作成：朴夏成
		修正：朴夏成
		修正日：2024/02/21
	*/

	/* 修正前：
  public function ReadRandByTypenLevelnNum($type, $level, $num)
  {
    $qry = $this->sql->select("question_pool")->where(["question_type" => $type, "question_level" => $level])->order(new Expression("Rand()"))->limit($num);
    return $this->sql->prepareStatementForSqlObject($qry)->execute();
  }
	*/

	/* 修正後： */
  public function ReadRandForExam($type, $academic, $career, $certificate, $num)
  {
    $whereData = array (
      "question_type" => $type,
      "academic" => $academic,
      "career" => $career,
      "certificate" => $certificate
    );

    $qry = $this->sql->select("question_pool")->where($whereData)->order(new Expression("Rand()"))->limit($num);
    return $this->sql->prepareStatementForSqlObject($qry)->execute();
  }
	/* ここまで */
}
