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

	public function ReadAll()
	{
		$qry = $this->sql->select("option");
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	public function ReadValid() {
		$qry = $this->sql->select("option")->where(["del_flag" => "N"])->order("text");
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	public function ReadByText($text) {
		$qry = $this->sql->select("option")->where(["text" => $text]);
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
	}

	public function ReadOption($whereData) {
		$qry = $this->sql->select("option")->where($whereData);
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
	}
}
