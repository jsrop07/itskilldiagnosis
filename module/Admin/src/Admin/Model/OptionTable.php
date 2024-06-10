<?php

namespace Admin\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;

class OptionTable
{
	public function __construct()
	{
		if (is_file($_SERVER["DOCUMENT_ROOT"] . "/../config/autoload/local.php")) {
			$this->config = require $_SERVER["DOCUMENT_ROOT"] . "/../config/autoload/local.php";
		} else {
			$this->config = require $_SERVER["DOCUMENT_ROOT"] . "/../config/autoload/global.php";
		}
		
		$dbArr = $this->config["db"];
		$adapter = new Adapter($dbArr);

		$this->adapter = $adapter;

		$this->sql = new Sql($this->adapter);
	}

	public function ReadByIdx($idx) {
		$qry = $this->sql->select("option")->where(["idx" => $idx]);
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
	}

	public function ReadByText($text) {
		$qry = $this->sql->select("option")->where(["text" => $text]);
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
	}

	public function ReadAll()
	{
		$qry = $this->sql->select("option");
		return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
	}

	/*
		作成：朴昰成
		修正：朴昰成
		修正日：24/06/11
	*/

	/* 修正前：
		public function ReadValid() {
			$qry = $this->sql->select("option")->where(["del_flag" => "N"])->order("text");
			return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
		}
	*/

	/* 修正後： */
	public function ReadValid() {
		$qry = $this->sql->select("option")->columns(["idx", "type", "text", "class_upper"])->where(["del_flag" => "N"])->order("text");
		$result = $this->sql->prepareStatementForSqlObject($qry)->execute();
		return iterator_to_array($result);
	}
	/* ここまで */

	public function ReadByOption($whereData) {
		$qry = $this->sql->select("option")->where($whereData);
		return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
	}
}
