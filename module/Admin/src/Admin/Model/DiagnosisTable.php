<?php

namespace Admin\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

class DiagnosisTable {
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

	/** Insert Record
	 * @return array $valueData
	*/
	public function CreateDiagnosis($valueData) {
		$qry = $this->sql->insert("diagnosis")->values($valueData);
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	/** Read Table records for List
	 * @return mixed Records
	*/
	public function ReadAllList() {
		$where = new Where();
		$where->isNull("date_end");

		$qry = $this->sql->select("diagnosis")->where($where)->order("date_start DESC");
		return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
	}
	public function GetAllList() {
		$where = new Where();
		$where->isNull("date_end");

		$qry = $this->sql->select("diagnosis")->where($where)->order("date_start DESC");
		$paginatorAdapter = new DbSelect($qry, $this->adapter);
		return new Paginator($paginatorAdapter);
	}
	
	/** Read for List by Search data 
	 * @param array $whereDatas [key => data]
	 * @return mixed Records Array
	*/
	public function GetListByOption($whereData) {
		$where = new Where();
		$where->isNull("date_end");
		$field = array_keys($whereData)[0];
		if ($field == "class2nd") {
			$where->and->equalTo("text", $whereData["class2nd"]);
			$qry = $this->sql->select("diagnosis")->where($where);
			$qry->join("option", "diagnosis.class2nd = option.idx", array("text" => "text"), "INNER");
			$qry->order("date_start DESC");
		}
		else {
			$where->and->equalTo($field, $whereData[$field]);
			$qry = $this->sql->select("diagnosis")->where($where)->order("date_start DESC");
		}
		$paginatorAdapter = new DbSelect($qry, $this->adapter);
		return new Paginator($paginatorAdapter);
	}

	/** Read Table record By idx
	 * @param int $idx
	 * @return mixed Record
	 */
	public function ReadByIdx($idx) {
		$qry = $this->sql->select("diagnosis")->where(["idx" => $idx]);
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
	}

	/** Read Table record By Code
	 * @param string $code
	 * @return mixed Record
	 */
	public function ReadByCode($code) {
		$qry = $this->sql->select("diagnosis")->where(["code" => $code]);
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
	}

	/*
		作成：朴昰成
		作成日：24/05/22
	*/
	/** Read Table record By Code
	 * @param string $code RecordTables diagnosis_code
	 * @param string $date RecordTables diagnosis_date
	 * @return mixed Record
	 */
	public function ReadForRecordByCodenDate($code, $date) {
		$where = new Where();
		$where->equalTo("code", $code)
			->and->lessThanOrEqualTo("date_start", $date);
		$qry = $this->sql->select("diagnosis")->where($where)->order("date_start DESC")->limit(1);
		$result = $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
		return $result;
	}
	/* ここまで */

	/** Update date_end By idx
	 * @param int $idx
	*/
	public function RemoveDiagnosis($idx) {
		$qry = $this->sql->update("diagnosis")->where(["idx" => $idx])->set(["date_end" => date("Y-m-d H:i:s")]);
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}
}