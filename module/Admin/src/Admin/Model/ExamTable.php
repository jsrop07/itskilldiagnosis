<?php

namespace Admin\Model;

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

class ExamTable
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

  public function createExam($post)
  {
		/* データの変更
			作成：朴夏成
			修正：朴夏成
			修正日：2024/02/22
		*/

		/* 修正前：
    $data = array(
      "url" => $post["url"],
      "user_id" => $post["id"],
      "user_pw" => $post["password"],
      "name" => $post["name"],
      "write_date" => date("Y-m-d H:i:s"),
      "question_level" => $post["level"],
      "question_nums" => $post["num"],
      "question_data" => $post["question_data"],
    );
		*/

		/* 修正後： */
		$data = array(
			"url" => $post["url"],
			"user_id" => $post["id"],
			"user_pw" => $post["password"],
			"name" => $post["name"],
			"write_date" => date("Y-m-d H:i:s"),
			"question_nums" => $post["num"],
		);
		
		if (isset($post["academic"]) && $post["academic"] != "") { $data["academic"] = $post["academic"]; }
		if (isset($post["career"]) && $post["career"] != "") { $data["career"] = $post["career"]; }
		if (isset($post["certificates"]) && $post["certificates"] != "") { $data["certificates"] = $post["certificates"]; }
		if (isset($post["question_data"])) { $data["question_data"] = $post["question_data"]; }
		/* ここまで */

    $qry = $this->sql->insert("exam")->values($data);
    return $this->sql->prepareStatementForSqlObject($qry)->execute();
  }

  public function readByUrl($url)
  {
    $qry = $this->sql->select("exam")->where(["url" => $url]);
    return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
  }

  public function readTenByPage($page)
  {
    $index = ($page - 1) * 10;
    $qry = $this->sql->select("exam")->order("write_date DESC")->limit(10)->offset($index);
    return $this->sql->prepareStatementForSqlObject($qry)->execute();
  }

  public function readCount()
  {
    $qry = $this->sql->select("exam");
    return count($this->sql->prepareStatementForSqlObject($qry)->execute());
  }

  public function readByIdx($idx)
  {
    $qry = $this->sql->select("exam")->where(["idx" => $idx]);
    return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
  }

  public function login($url, $id, $password)
  {
    $qry = $this->sql->select("exam")->where(
      array(
        "url" => $url,
        "user_id" => $id,
      )
    );

    $result = $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
    if (empty($result)) {
      return "wrong id";
    }

    if ($result["user_pw"] == $password) {
      return "success";
    }
    return "wrong password";
  }

	/* 機能の追加
		作成：朴昰成
		作成日：2024/03/05
	*/
	public function updateExamSetting($url, $post) {
		$datas = array(
			"academic" => $post["academic"],
			"question_type" => $post["type"],
			"career" => $post["career"],
			"certificates" => $post["certificates"],
			"question_data" => $post["question_data"]	
		);
		
		$qry = $this->sql->update("exam")->where(["url" => $url])->set($datas);
		$this->sql->prepareStatementForSqlObject($qry)->execute();
	}
	/* ここまで */

	/* 機能の追加変更
		作成：朴昰成
		修正：朴昰成
		修正日：2024/03/18
	*/

	/*　修正前：
  public function updateSubmit($url, $answers, $point)
  {
    $qry = $this->sql->update("exam")->where(["url" => $url])->set(
      array(
        "answer_data" => $answers,
        "get_point" => $point,
        "execute_date" => date("Y-m-d H:i:s"),
      )
    );
    return $this->sql->prepareStatementForSqlObject($qry)->execute();
  }
	*/

	/* 修正後： */
	/** Update exam data */
  public function updateSubmit($url, $answers, $point)
  {
		$datas = array(
			"execute_date" => date("Y-m-d H:i:s"),
			"get_point" => $point,
		);
		if (!is_null($answers)) { $datas["answer_data"] = $answers; }

		$qry = $this->sql->update("exam")->where(["url" => $url])->set($datas);
    return $this->sql->prepareStatementForSqlObject($qry)->execute();
  }
	/* ここまで */
}
