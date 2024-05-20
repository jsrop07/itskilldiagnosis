<?php

namespace Admin\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;
use Zend\Paginator\Adapter\DbSelect;

class ApplicantTable {
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

	public function ReadByIdx($idx) {
		$qry = $this->sql->select("applicant")->where(["idx" => $idx]);
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
	}

	public function ReadByName($name) {
		$where = new Where();
		$where->like("name", "%" . $name . "%")->or->like("kana", "%" . $name . "%");

		$qry = $this->sql->select("applicant")->where($where);
		return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
	}
}