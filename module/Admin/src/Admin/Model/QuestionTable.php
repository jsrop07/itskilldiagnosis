<?php

namespace Admin\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;

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

	public function ReadQuestion() {
		$qry = $this->sql->select("question")->order("date_regist DESC");
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	public function ReadByAdminCode($code)
	{
		$where = new Where();
		$where->isNotNull("date_regist")->or->equalTo("admin_regist", $code);

		$qry = $this->sql->select("question")->where($where)->order("date_regist DESC");
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	public function ReadSaveByAdminCode($code)
	{
		$qry = $this->sql->select("question")->where(["status" => 0, "admin_create" => $code]);
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
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

	public function ReadByIndex($idx) {
		$qry = $this->sql->select("question")->where(["idx" => $idx]);
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
	}

	public function ReadNotRegist() {
		$qry = $this->sql->select("question")->where(["status" => 0]);
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	public function ReadNotRegistByRegister($code) {
		$qry = $this->sql->select("question")->where(["status" => 0, "admin_regist" => $code]);
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	public function ReadByCreater_Register($code) {
		$where = new Where();
		$where->equalTo("admin_create", $code)->or->equalTo("admin_regist", $code);

		$qry = $this->sql->select("question")->where($where);
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	public function UpdateQuestion($datas) {
		$idx = $datas["idx"];
		unset($datas["idx"]);

		$datas["date_update"] = date("Y-m-d H:i:s");

		$qry = $this->sql->update("question")->where(["idx" => $idx])->set($datas);
		$this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	public function UpdateToRegistByIdx($idx) {
		$date = date("Y-m-d H:i:s");
		
		$qry = $this->sql->update("question")->where(["idx" => $idx])
			->set(["status" => 2, "date_regist" => $date, "date_update" => $date]);
		$this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	public function UpdateToRegistByMaster_Idx($code, $idx) {
		$date = date("Y-m-d H:i:s");
		
		$qry = $this->sql->update("question")->where(["idx" => $idx])
			->set(["status" => 2, "admin_regist" => $code, "date_regist" => $date, "date_update" => $date]);
		$this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	public function DeleteQuestionByIdx($idx) {
		$qry = $this->sql->delete("question")->where(["idx" => $idx]);
		$this->sql->prepareStatementForSqlObject($qry)->execute();
	}
}
