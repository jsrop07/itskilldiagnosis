<?php

namespace Admin\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Predicate\Expression;

class QuestionTable {
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

	public function ReadByAdminCode_Num_Page($code, $num, $page)
	{
		$index = ($page - 1) * $num;
		$where = new Where();
		$where->notEqualTo("date_regist", null)
			->or->equalTo("admin_regist", $code);

		$qry = $this->sql->select("question")->where($where)
			->order("date_regist DESC")->limit($num)->offset($index);
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}

  public function readCount()
  {
    $qry = $this->sql->select("question_pool");
    return count($this->sql->prepareStatementForSqlObject($qry)->execute());
  }

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

  public function readByIdx($idx)
  {
    $qry = $this->sql->select("question_pool")->where(["idx" => $idx]);
    return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
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
	/** Get Question Data */
  public function ReadRandForExam($datas) {
    $whereDatas = array (
      "question_type" => $datas["type"],
      "academic" => $datas["academic"],
      "career" => $datas["career"]
    );
		
		if ($datas["certificates"] == NULL) { $whereDatas["certificate"] = 0; }
		else { $whereDatas["certificate"] = 1; }

    $qry = $this->sql->select("question_pool")->where($whereDatas)->order(new Expression("Rand()"))->limit($datas["num"]);
    return $this->sql->prepareStatementForSqlObject($qry)->execute();
  }
	/* ここまで */
}
