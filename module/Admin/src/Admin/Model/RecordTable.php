<?php

namespace Admin\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

class RecordTable {
	public function __construct() {
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

	public function ReadAllNewList() {
		$qry = $this->sql->select("record")->where(["code" => null])->order(["write_date" => "ASC"]);
		return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
	}
	public function ReadAllRestList() {
		$where = new Where();
		$where->isNotNull("code");

		$qry = $this->sql->select("record")->where($where)->order(["write_date" => "ASC"]);
		return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
	}
	public function GetAllList() {
		$qry = $this->sql->select("record");
		$paginatorAdapter = new DbSelect($qry, $this->adapter);
		return new Paginator($paginatorAdapter);
	}
}