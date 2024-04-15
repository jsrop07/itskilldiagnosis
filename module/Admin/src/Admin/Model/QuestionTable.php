<?php

namespace Admin\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Predicate\IsNotNull;

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

	public function CreateQuestion($datas)
	{
		$datas["date_update"] = date("Y-m-d H:i:s");

		$qry = $this->sql->insert("question")->values($datas);
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	public function ReadByAdminCode($code)
	{
		$where = new Where();
		$where->isNotNull("date_regist")->or->equalTo("admin_regist", $code);

		$qry = $this->sql->select("question")->where($where)->order("date_regist DESC");
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	public function ReadByAdminCode_RegisterCode($code_user, $code_register)
	{
		$where = new Where();
		$where
			->nest()	// (admin_regist = $code_user AND admin_regist = $code_register)
				->equalTo("admin_regist", $code_user)
				->and->equalTo("admin_regist", $code_register)
			->unnest()
			->or->nest()	// (admin_regist = $code_register AND date_regist <> null)
				->equalTo("admin_regist", $code_register)
				->and->isNotNull("date_regist")
			->unnest();

		$qry = $this->sql->select("question")->where($where)->order("date_regist DESC");
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	public function ReadByIndex($index) {
		$qry = $this->sql->select("question")->where(["idx" => $index]);
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
	}
}
