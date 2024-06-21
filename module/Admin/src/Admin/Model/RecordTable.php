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
		$result = iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
		return $result;
	}
	public function ReadAllRestList() {
		$where = new Where();
		$where->isNotNull("diagnosis_code");

		$qry = $this->sql->select("record")->where($where)->order(["apply_date" => "DESC"]);
		$result = iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
		return $result;
	}
	public function GetAllList() {
		/*
			作成：朴昰成
			修正：朴昰成
			修正日：24/06/21
		*/
		
		/* 修正前：
		$qry = $this->sql->select("record");
		$paginatorAdapter = new DbSelect($qry, $this->adapter);
		return new Paginator($paginatorAdapter);
		*/

		/* 修正後： */
		// $order[] = "ISNULL(diagnosis_code)";
		// $order["apply_date"] = "desc";
		$qry = $this->sql->select("record")->join("applicant", "record.applicant_idx = applicant.idx", array("name" => "name", "kana" => "kana"), "INNER");
		$qry->order(new \Zend\Db\Sql\Expression("diagnosis_code IS NULL DESC, apply_date DESC"));

		$paginatorAdapter = new DbSelect($qry, $this->adapter);
		$paginator = new Paginator($paginatorAdapter);
		return $paginator;
		/* ここまで */
	}

	/*
		作成：朴昰成
		作成日：24/06/21
	*/
	public function GetListBySearch($whereDatas) {
		$where = new Where();
		// $where->isNotNull("idx");
		foreach ($whereDatas as $field => $data) {
			if ($field == "name") {
				$where->and->nest()->like("name", "%" . $data . "%")
					->or->like("kana", "%" . $data . "%")->unnest();
			}
			else if (substr($data, 0, 4) == "not-") {
				$data = explode("-", $data)[1];
				$where->and->isNotNull($field);
				$where->and->notEqualTo($field, $data);
			}
			else {
				switch($data) {
					case "null":
						$where->and->isNull($field);
						break;
					case "not null":
						$where->and->isNotNull($field);
						break;
					default:
						$where->and->equalTo($field, $data);
						break;
				}
			}
		}

		$qry = $this->sql->select("record")->join("applicant", "record.applicant_idx = applicant.idx", array("name" => "name", "kana" => "kana"), "INNER");
		$qry->where($where)->order(new \Zend\Db\Sql\Expression("diagnosis_date IS NULL DESC, apply_date DESC"));

		$paginatorAdapter = new DbSelect($qry, $this->adapter);
		$paginator = new Paginator($paginatorAdapter);
		return $paginator;
	}

	/* ここまで */
	public function ReadApplyData() {
		$qry = $this->sql->select("record")->where(["request_date" => null]);
		$result = iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
		return $result;
	}

	public function ReadRequestData() {
		$where = new Where();
		$where->isNotNull("request_date")->and->isNull("execute_date");

		$qry = $this->sql->select("record")->where($where);
		$result = iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
		return $result;
	}

	public function ReadByIdx($idx) {
		$qry = $this->sql->select("record")->where(["idx" => $idx]);
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
	}

	public function ReadNewListByOffset($offset) {
		$qry = $this->sql->select("record")->where(["diagnosis_date" => null])->order(["apply_date" => "DESC"])->limit(10)->offset($offset);
		$result = iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
		return $result;
	}
	
	public function ReadRestListByOffsetnLimit($offset, $limit) {
		$where = new Where();
		$where->isNotNull("diagnosis_date");

		$qry = $this->sql->select("record")->where($where)->order(["apply_date" => "DESC"])->limit($limit)->offset($offset);
		$result = iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
		return $result;
	}

	public function ReadNewListBySearchnOffset($whereDatas, $offset) {
		$where = new Where();
		$where->isNull("diagnosis_date");
		foreach ($whereDatas as $field => $data) {
			if ($field == "name") {
				$where->and->nest()->like("name", "%" . $data . "%")
					->or->like("kana", "%" . $data . "%")->unnest();
			}
			else if ($field == "date") {
				$where->and->like("date_schedule", $data . "%");
			}
			/*
				作成：朴昰成
				作成日：24/06/20
			*/
			else if (substr($data, 0, 4) == "not-") {
				$data = explode("-", $data)[1];
				$where->and->isNotNull($field);
				$where->and->notEqualTo($field, $data);
			}
			/* ここまで */
			else {
				switch($data) {
					case "null":
						$where->and->isNull($field);
						break;
					case "not null":
						$where->and->isNotNull($field);
						break;
					default:
						$where->and->equalTo($field, $data);
						break;
				}
			}
		}

		/*
			作成：朴昰成
			修正：朴昰成
			修正日：24/06/21
		*/

		/* 修正前：

		$qry = $this->sql->select("record")->where($where)->order(["apply_date" => "DESC"])->offset($offset)->limit(10);
		$qry->join("applicant", "record.applicant_idx = applicant.idx", array("name" => "name", "kana" => "kana"), "INNER");
		$result = iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
		return $result;
		*/

		/* 修正後： */
		$order["diagnosis_code"] = "is null desc";
		$order["apply_date"] = "desc";

		$qry = $this->sql->select("record")->where($where)->order($order)->offset($offset)->limit(10);
		$qry->join("applicant", "record.applicant_idx = applicant.idx", array("name" => "name", "kana" => "kana"), "INNER");
		$result = iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
		return $result;
		/* ここまで */
	}
	public function ReadRestListBySearchnOffsetnLimit($whereDatas, $offset, $limit) {
		$where = new Where();
		$where->isNotNull("diagnosis_date");
		foreach ($whereDatas as $field => $data) {
			if ($field == "name") {
				$where->and->nest()->like("name", "%" . $data . "%")
					->or->like("kana", "%" . $data . "%")->unnest();
			}
			else if ($field == "date") {
				$where->and->like("date_schedule", $data . "%");
			}
			/*
				作成：朴昰成
				作成日：24/06/20
			*/
			else if (substr($data, 0, 4) == "not-") {
				$data = explode("-", $data)[1];
				$where->and->isNotNull($field);
				$where->and->notEqualTo($field, $data);
			}
			/* ここまで */
			else {
				switch($data) {
					case "null":
						$where->and->isNull($field);
						break;
					case "not null":
						$where->and->isNotNull($field);
						break;
					default:
						$where->and->equalTo($field, $data);
						break;
				}
			}
		}

		/*
			作成：朴昰成
			修正：朴昰成
			修正日：24/06/21
		*/

		/* 修正前：
		$qry = $this->sql->select("record")->where($where)->order(["apply_date" => "DESC"])->offset($offset)->limit($limit);
		$qry->join("applicant", "record.applicant_idx = applicant.idx", array("name" => "name", "kana" => "kana"), "INNER");
		$result = iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
		return $result;
		*/

		/* 修正後： */
		$order["diagnosis_code"] = "is null desc";
		$order["apply_date"] = "desc";

		$qry = $this->sql->select("record")->where($where)->order($order)->offset($offset)->limit($limit);
		$qry->join("applicant", "record.applicant_idx = applicant.idx", array("name" => "name", "kana" => "kana"), "INNER");
		$result = iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
		return $result;
		/* ここまで */
	}
	/*
		作成：朴昰成
		削除：朴昰成
		削除日：24/06/21
	*/

	/* 削除前：
	public function GetListBySearch($whereDatas) {
		$where = new Where();
		foreach ($whereDatas as $field => $data) {
			if ($field == "name") {
				$where->and->nest()->like("name", "%" . $data . "%")
					->or->like("kana", "%" . $data . "%")->unnest();
			}
			else if ($field == "date") {
				$where->and->like("date_schedule", $data . "%");
			}
			else {
				switch($data) {
					case "null":
						$where->and->isNull($field);
						break;
					case "not null":
						$where->and->isNotNull($field);
						break;
					default:
						$where->and->equalTo($field, $data);
						break;
				}
			}
		}

		$qry = $this->sql->select("record")->where($where);
		$qry->join("applicant", "record.applicant_idx = applicant.idx", array("name" => "name", "kana" => "kana"), "INNER");
		$paginatorAdapter = new DbSelect($qry, $this->adapter);
		return new Paginator($paginatorAdapter);
	}
	*/

	public function ReadRecord($selectDatas, $whereDatas) {
		$qry = $this->sql->select("record", $selectDatas)->where($whereDatas);
		print_r($qry->__toString());
		exit;
	}

	public function CountAllData() {
		$qry = $this->sql->select("record")->columns(array('COUNT'=>new \Zend\Db\Sql\Expression('COUNT(*)')));
		$result = $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
		return $result["COUNT"];
	}
	public function CountApplyData() {
		$qry = $this->sql->select("record")->where(["request_date" => null])->columns(array('COUNT'=>new \Zend\Db\Sql\Expression('COUNT(*)')));
		$result = $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
		return $result["COUNT"];
	}
	public function CountRequestData() {
		$where = new Where();
		$where->isNotNull("request_date")->and->isNull("rank");

		$qry = $this->sql->select("record")->where($where)->columns(array('COUNT'=>new \Zend\Db\Sql\Expression('COUNT(*)')));
		$result = $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
		return $result["COUNT"];
	}
	public function CountOverData() {
		$where = new Where();
		$where->isNotNull("request_date")->and->isNotNull("rank");

		$qry = $this->sql->select("record")->where($where)->columns(array("COUNT"=>new \Zend\Db\Sql\Expression("COUNT(*)")));
		$result = $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
		return $result["COUNT"];
	}
	public function CountNewData() {
		$qry = $this->sql->select("record")->where(["diagnosis_date" => null])->columns(array('COUNT'=>new \Zend\Db\Sql\Expression('COUNT(*)')));
		$result = $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
		return $result["COUNT"];
	}
	
	public function CountAllDataBySearch($whereDatas) {
		$where = new Where();
		$where->isNotNull("apply_date");
		foreach ($whereDatas as $field => $data) {
			if ($field == "name") {
				$where->and->nest()->like("name", "%" . $whereDatas . "%")
					->or->like("kana", "%" . $whereDatas . "%")->unnest();
			}
			else if ($field == "date") {
				$where->and->like("date_schedule", $data . "%");
			}
			else {
				switch($data) {
					case "null":
						$where->and->isNull($field);
						break;
					case "not null":
						$where->and->isNotNull($field);
						break;
					default:
						$where->and->equalTo($field, $data);
						break;
				}
			}
		}

		$qry = $this->sql->select("record")->where($where)->columns(array('COUNT'=>new \Zend\Db\Sql\Expression('COUNT(*)')));
		$result = $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
		return $result["COUNT"];
	}
	public function CountNewDataBySearch($whereDatas) {
		$where = new Where();
		$where->isNull("diagnosis_date");
		foreach ($whereDatas as $field => $data) {
			if ($field == "name") {
				$where->and->nest()->like("name", "%" . $data . "%")
					->or->like("kana", "%" . $data . "%")->unnest();
			}
			else if ($field == "date") {
				$where->and->like("date_schedule", $data . "%");
			}
			else {
				switch($data) {
					case "null":
						$where->and->isNull($field);
						break;
					case "not null":
						$where->and->isNotNull($field);
						break;
					default:
						$where->and->equalTo($field, $data);
						break;
				}
			}
		}

		$qry = $this->sql->select("record")->where($where)->columns(array("COUNT" => new \Zend\Db\Sql\Expression("COUNT(*)")));	
		$qry->join("applicant", "record.applicant_idx = applicant.idx", [], "INNER");
		$result = $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
		return $result["COUNT"];
	}
	public function CountApplyDataBySearch($whereDatas) {
		$where = new Where();
		$where->isNull("request_date");
		foreach ($whereDatas as $field => $data) {
			if ($field == "name") {
				$where->and->nest()->like("name", "%" . $data . "%")
					->or->like("kana", "%" . $data . "%")->unnest();
			}
			else if ($field == "date") {
				$where->and->like("date_schedule", $data . "%");
			}
			else {
				switch($data) {
					case "null":
						$where->and->isNull($field);
						break;
					case "not null":
						$where->and->isNotNull($field);
						break;
					default:
						$where->and->equalTo($field, $data);
						break;
				}
			}
		}

		$qry = $this->sql->select("record")->where($where)->columns(array('COUNT'=>new \Zend\Db\Sql\Expression('COUNT(*)')));
		$result = $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
		return $result["COUNT"];
	}
	public function CountRequestDataBySearch($whereDatas) {
		$where = new Where();
		$where->isNotNull("request_date")->and->isNull("execute_date");
		foreach ($whereDatas as $field => $data) {
			if ($field == "name") {
				$where->and->nest()->like("name", "%" . $data . "%")
					->or->like("kana", "%" . $data . "%")->unnest();
			}
			else if ($field == "date") {
				$where->and->like("date_schedule", $data . "%");
			}
			else {
				switch($data) {
					case "null":
						$where->and->isNull($field);
						break;
					case "not null":
						$where->and->isNotNull($field);
						break;
					default:
						$where->and->equalTo($field, $data);
						break;
				}
			}
		}

		$qry = $this->sql->select("record")->where($where)->columns(array('COUNT'=>new \Zend\Db\Sql\Expression('COUNT(*)')));
		$result = $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
		return $result["COUNT"];
	}

	public function UpdateByIdx($idx, $setDatas) {
		$qry = $this->sql->update("record")->where(["idx" => $idx])->set($setDatas);
		$result = $this->sql->prepareStatementForSqlObject($qry)->execute();
		return $result;
	}

	public function RequestByIdx($idx) {
		$qry = $this->sql->update("record")->where(["idx" => $idx])->set(["request_date" => date("Y-m-d H:i:s")]);
		$result = $this->sql->prepareStatementForSqlObject($qry)->execute();
		return $result;
	}
}