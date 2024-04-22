<?php

namespace Admin\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

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
		$qry = $this->sql->insert("question")->values($datas);
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	public function ReadQuestion() {
		$qry = $this->sql->select("question")->order("date_regist DESC");
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	public function ReadAllList() {
		$qry = $this->sql->select("question")->where(["date_delete" => null])->order("date_approve desc");
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	public function ReadListByRegist($code) {
		$where = new Where();
		$where
			->isNull("date_delete")
			->and->nest()
				->isNotNull("date_approve")
				->or->equalTo("admin_regist", $code)
			->unnest();
		
		$qry = $this->sql->select("question")->where($where)->order("date_approve desc");
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	public function ReadListByCode($code) {
		$where = new Where();
		$where
			->equalTo("status", 3);
			$where->or->nest()
				->notEqualTo("status", 0)
				->and->nest()
					->equalTo("admin_create", $code)
					->or->equalTo("admin_regist", $code)
				->unnest()
			->unnest();
			

		$qry = $this->sql->select("question")->where($where);
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	public function ReadByIdx($idx) {
		$qry = $this->sql->select("question")->where(["idx" => $idx]);
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
	}

	public function ReadListByOption($optionDatas) {
		$order = "date_approve DESC";
		if (isset($optionDatas["align"])) {
			$order = [str_replace("_", " ", $optionDatas["align"]), "date_approve DESC"];
			unset($optionDatas["align"]);
		}

		$where = new Where();
		$where->notEqualTo("status", 0);
		foreach ($optionDatas as $key => $value) {
			$where->and->equalTo($key, $value);
		}
		
		$qry = $this->sql->select("question")->where($where)->order($order);
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	public function ReadListByCode_Option($code, $optionDatas) {
		$where = new Where();
		$where
			->nest()->equalTo("status", 3)
				->or->nest()
					->notEqualTo("status", 0)
					->and->nest()
						->equalTo("admin_create", $code)
						->or->equalTo("admin_regist", $code)
					->unnest()
				->unnest()
			->unnest();
		foreach ($optionDatas as $key => $value) {
			$where->and->equalTo($key, $value);
		}
		
		$qry = $this->sql->select("question")->where($where);
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
		$qry = $this->sql->select("question")->where(["status != 3"]);
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	public function ReadNotRegistByRegister($code) {
		$qry = $this->sql->select("question")->where(["status != 3", "admin_regist" => $code]);
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	public function ReadByCreater_Register($code) {
		$where = new Where();
		$where->equalTo("admin_create", $code)->or->equalTo("admin_regist", $code);

		$qry = $this->sql->select("question")->where($where);
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	public function UpdateQuestion($whereData, $setData) {
		$qry = $this->sql->update("question")->where($whereData)->set($setData);
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	public function UpdateQuestiont($datas) {
		$idx = $datas["idx"];
		unset($datas["idx"]);

		$datas["date_update"] = date("Y-m-d H:i:s");

		$qry = $this->sql->update("question")->where(["idx" => $idx])->set($datas);
		$this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	public function UpdateToRegistByIdx($idx, $code) {
		$date = date("Y-m-d H:i:s");
		
		$qry = $this->sql->update("question")->where(["idx" => $idx])
			->set(["admin_approve" => $code, "status" => 21, "date_approve" => $date]);
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

	public function GetAllList() {
		$qry = $this->sql->select("question")->where(["date_delete" => null])->order("date_regist desc");

		$paginatorAdapter = new DbSelect($qry, $this->adapter);
		return new Paginator($paginatorAdapter);
	}

	public function GetListByRegist($code) {
		$where = new Where();
		$where
			->isNull("date_delete")
			->and->nest()
				->isNotNull("date_approve")
				->or->equalTo("admin_regist", $code)
			->unnest();

		$qry = $this->sql->select("question")->where($where)->order("date_approve desc");

		$paginatorAdapter = new DbSelect($qry, $this->adapter);
		return new Paginator($paginatorAdapter);
	}

	public function GetListByOption($optionDatas) {
		$order = "date_approve DESC";
		if (isset($optionDatas["align"])) {
			$order = [str_replace("_", " ", $optionDatas["align"]), "date_approve DESC"];
			unset($optionDatas["align"]);
		}

		$where = new Where();
		$where->notEqualTo("status", 0);
		foreach ($optionDatas as $key => $value) {
			$where->and->equalTo($key, $value);
		}

		$qry = $this->sql->select("question")->where($where)->order($order);

		$paginatorAdapter = new DbSelect($qry, $this->adapter);
		return new Paginator($paginatorAdapter);
	}

	public function getNoticeList($params) {
		$qry = $this->sql->select("question")->where(["date_delete" => null])->order("date_regist desc");

		$paginatorAdapter = new DbSelect($qry ,$this->adapter);
		$return = new Paginator($paginatorAdapter);
		return $return;
	}
}
