<?php

namespace Admin\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

class DiagnosisTable {
	public function __construct() {
		//Local設定ファイルがある場合、Local設定を優先する
		if (is_file($_SERVER["DOCUMENT_ROOT"] . "/../config/autoload/local.php")) {
			$this->config = require $_SERVER["DOCUMENT_ROOT"] . "/../config/autoload/local.php";
		} else {
			$this->config = require $_SERVER["DOCUMENT_ROOT"] . "/../config/autoload/global.php";
		}

		//指定DB設定情報通り接続
		$dbArr = $this->config["db"];
		$adapter = new Adapter($dbArr);
		//Adapter設定
		$this->adapter = $adapter;
		//簡単に共通Sql宣言
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
		$where->isNotNull("date_create");

		$qry = $this->sql->select("diagnosis")->where($where)->order("date_create DESC");
		return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
	}
	public function GetAllList() {
		$where = new Where();
		$where->isNotNull("date_create");

		$qry = $this->sql->select("diagnosis")->where($where);
		$paginatorAdapter = new DbSelect($qry, $this->adapter);
		return new Paginator($paginatorAdapter);
	}
	
	/** Read for List by Search data 
	 * @param array $whereDatas [key => data]
	 * @return mixed Records Array
	*/
	public function ReadListByOption($whereData) {
		$qry = $this->sql->select("diagnosis")->where($whereData)->order("date_create DESC");
		return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
	}
	public function GetListByOption($whereData) {
		$qry = $this->sql->select("diagnosis")->where($whereData)->order("date_create DESC");
		$paginatorAdapter = new DbSelect($qry, $this->adapter);
		return new Paginator($paginatorAdapter);
	}

	/** Read Table record By Code
	 * @param string $code
	 * @return mixed Record
	 */
	public function ReadByCode($code) {
		$qry = $this->sql->select("diagnosis")->where(["code" => $code]);
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
	}
}