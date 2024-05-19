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
		$qry = $this->sql->select("record")->where(["diagnosis_code" => null])->order(["apply_date" => "DESC"]);
		return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
	}
	public function ReadAllRestList() {
		$where = new Where();
		$where->isNotNull("diagnosis_code");

		$qry = $this->sql->select("record")->where($where)->order(["apply_date" => "DESC"]);
		return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
	}
	public function GetAllList() {
		$qry = $this->sql->select("record");
		$paginatorAdapter = new DbSelect($qry, $this->adapter);
		return new Paginator($paginatorAdapter);
	}

	public function ReadApplyData() {
		$qry = $this->sql->select("record")->where(["request_date" => null]);
		return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
	}

	public function ReadRequestData() {
		$where = new Where();
		$where->isNotNull("request_date")->and->isNull("execute_date");

		$qry = $this->sql->select("record")->where($where);
		return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
	}

	public function ReadByIdx($idx) {
		$qry = $this->sql->select("record")->where(["idx" => $idx]);
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
	}

	public function ReadNewListByOffset($offset) {
		$qry = $this->sql->select("record")->where(["diagnosis_date" => null])->order(["apply_date" => "DESC"])->limit(10)->offset($offset);
		return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
	}
	
	public function ReadRestListByOffsetnLimit($offset, $limit) {
		$where = new Where();
		$where->isNotNull("diagnosis_date");

		$qry = $this->sql->select("record")->where($where)->order(["apply_date" => "DESC"])->limit($limit)->offset($offset);
		return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
	}

	public function ReadNewListBySearchnOffsetnAlign($whereDatas, $offset, $order) {
		$qry = $this->sql->select("record")->where(["diagnosis_date" => null])->order($order)->limit(10)->offset($offset);
		return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
	}
	
	public function ReadRestListBySearchnOffsetnLimitnAlign($whereDatas, $offset, $limit, $order) {
		$where = new Where();
		$where->isNotNull("diagnosis_date");

		$qry = $this->sql->select("record")->where($where)->order($order)->limit($limit)->offset($offset);
		return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
	}

	public function CountRecordData() {
		$qry = $this->sql->select("record")->columns(array('COUNT'=>new \Zend\Db\Sql\Expression('COUNT(*)')));
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current()["COUNT"];
	}
	public function CountApplyData() {
		$qry = $this->sql->select("record")->where(["request_date" => null])->columns(array('COUNT'=>new \Zend\Db\Sql\Expression('COUNT(*)')));
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current()["COUNT"];
	}
	public function CountRequestData() {
		$where = new Where();
		$where->isNotNull("request_date")->and->isNull("execute_date");

		$qry = $this->sql->select("record")->where($where)->columns(array('COUNT'=>new \Zend\Db\Sql\Expression('COUNT(*)')));
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current()["COUNT"];
	}
}