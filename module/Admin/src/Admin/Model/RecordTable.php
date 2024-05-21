<?php

namespace Admin\Model;

use PDOException;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Where;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Db\Sql\Predicate\Expression;

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

	/*
	　作成：朴昰成
		修正：朴昰成
		修正日：24/05/21
	*/

	/*　修正前：
		public function ReadNewListBySearchnOffsetnAlign($whereDatas, $offset, $order) {
			$where = new Where();
			$where->isNull("diagnosis_date");
			if (!empty($whereDatas)) {
				$where->and->nest()->like("name", "%" . $whereDatas . "%")
				->or->like("kana", "%" . $whereDatas . "%")->unnest();
			}

			$qry = $this->sql->select("record")->where($where)->order($order)->limit(10)->offset($offset);
			$qry->join("applicant", "record.applicant_idx = applicant.idx", array("name" => "name", "kana" => "kana"), "INNER");
			return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
		}
		public function ReadRestListBySearchnOffsetnLimitnAlign($whereDatas, $offset, $limit, $order) {
			$where = new Where();
			$where->isNotNull("diagnosis_date");
			if (!empty($whereDatas)) {
				$where->and->nest()->like("name", "%" . $whereDatas . "%")
				->or->like("kana", "%" . $whereDatas . "%")->unnest();
			}

			$qry = $this->sql->select("record")->where($where)->order($order)->limit(10)->offset($offset);
			$qry->join("applicant", "record.applicant_idx = applicant.idx", array("name" => "name", "kana" => "kana"), "INNER");
			return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
		}

		public function GetListBySearch($whereDatas) {
			$where = new Where();
			$where->isNotNull("diagnosis_date");
			if (!empty($whereDatas)) {
				$where->and->nest()->like("name", "%" . $whereDatas . "%")
				->or->like("kana", "%" . $whereDatas . "%")->unnest();
			}

			$qry = $this->sql->select("record")->where($where);
			$qry->join("applicant", "record.applicant_idx = applicant.idx", array("name" => "name", "kana" => "kana"), "INNER");
			$paginatorAdapter = new DbSelect($qry, $this->adapter);
			return new Paginator($paginatorAdapter);
		}
	*/

	/*　修正後：　*/
	public function ReadNewListBySearchnOffset($whereDatas, $offset) {
		$where = new Where();
		$where->isNull("diagnosis_date");
		foreach ($whereDatas as $field => $data) {
			if ($field == "name") {
				$where->and->nest()->like("name", "%" . $data . "%")
					->or->like("kana", "%" . $data . "%")->unnest();
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

		$qry = $this->sql->select("record")->where($where)->order(["apply_date" => "DESC"])->offset($offset)->limit(10);
		$qry->join("applicant", "record.applicant_idx = applicant.idx", array("name" => "name", "kana" => "kana"), "INNER");
		return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
	}
	public function ReadRestListBySearchnOffsetnLimit($whereDatas, $offset, $limit) {
		$where = new Where();
		$where->isNotNull("diagnosis_date");
		foreach ($whereDatas as $field => $data) {
			if ($field == "name") {
				$where->and->nest()->like("name", "%" . $data . "%")
					->or->like("kana", "%" . $data . "%")->unnest();
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

		$qry = $this->sql->select("record")->where($where)->order(["apply_date" => "DESC"])->offset($offset)->limit($limit);
		$qry->join("applicant", "record.applicant_idx = applicant.idx", array("name" => "name", "kana" => "kana"), "INNER");
		return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
	}
	public function GetListBySearch($whereDatas) {
		$where = new Where();
		foreach ($whereDatas as $field => $data) {
			if ($field == "name") {
				$where->and->nest()->like("name", "%" . $data . "%")
					->or->like("kana", "%" . $data . "%")->unnest();
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
	/*　ここまで　*/

	public function CountAllData() {
		// $qry = $this->sql->select("record")->columns(array('COUNT'=>new \Zend\Db\Sql\Expression('COUNT(*)')));
		// // $qry = $this->sql->select("record")->columns(array("request_date"=>"request_date","cnt"=>new \Zend\Db\Sql\Expression("count(*)")))->group(array());
		// // print_r($qry->getSqlString()); exit;
		// try{
		// 	$qry = $this->sql->select("record")->columns(array("class1st"));
		// 	$qry->group("case");
		// 	$return = $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
		// 	print_r($return);
		// }catch(\PDOException $e){
		// 	print_r($e->getMessage());
		// }
		// exit;
	// 	try {
	// 		// code to throw exceptions
	// 		$qry = $this->sql->select("record")->columns(array("class1st"));
	// 		$qry->group("case");
	// 		$return = $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
	// } catch (\Zend\Db\Adapter\Exception $e) {
	// 		echo $e->getMessage();
	// } catch (\Exception $e) {
	// 		// This is not an adapter exception, anyway its an exception.
	// 		echo $e->getMessage();
	// } finally {
	// 		// This block always executed, an exception thrown or not.
	// }
			$qry=$this->adapter->query("select count(*) as c from record group by class1st")->execute();
			foreach($qry as $q){
				print_r($q);
			}
			echo 'a<br/>';
			$qry=$this->sql->select("record")->group("class1st");
			echo 'b<br/>';
			print_r($qry->getSqlString());
			echo 'b2<br/>';
			$row = $this->sql->prepareStatementForSqlObject($qry)->execute();
			echo 'c<br/>';
			print_r($row);
			echo 'd<br/>';
			exit;
			
			
	exit;
		// return $this->sql->prepareStatementForSqlObject($qry)->execute()->current()["COUNT"];
	}
	public function CountApplyData() {
		$qry = $this->sql->select("record")->where(["request_date" => null])->columns(array('COUNT'=>new \Zend\Db\Sql\Expression('COUNT(*)')));
		$row = $this->sql->prepareStatementForSqlObject($qry)->execute()->current();

		return $row["COUNT"];
	}
	public function CountRequestData() {
		$where = new Where();
		$where->isNotNull("request_date")->and->isNull("execute_date");

		$qry = $this->sql->select("record")->where($where)->columns(array('COUNT'=>new \Zend\Db\Sql\Expression('COUNT(*)')));
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current()["COUNT"];
	}
	/*
		作成：朴昰成
		作成日：24/05/21
	*/
	public function CountNewData() {
		$qry = $this->sql->select("record")->where(["diagnosis_date" => null])->columns(array('COUNT'=>new \Zend\Db\Sql\Expression('COUNT(*)')));
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current()["COUNT"];
	}
	
	public function CountAllDataBySearch($whereDatas) {
		$where = new Where();
		$where->isNotNull("apply_date");
		foreach ($whereDatas as $field => $data) {
			if ($field == "name") {
				$where->and->nest()->like("name", "%" . $whereDatas . "%")
					->or->like("kana", "%" . $whereDatas . "%")->unnest();
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
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current()["COUNT"];
	}
	public function CountNewDataBySearch($whereDatas) {
		$where = new Where();
		$where->isNull("request_date");
		foreach ($whereDatas as $field => $data) {
			if ($field == "name") {
				$where->and->nest()->like("name", "%" . $data . "%")
					->or->like("kana", "%" . $data . "%")->unnest();
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
		$qry->group(array("p.product_id"));
		// print_r($qry->getSqlString()); exit;
		$qry->columns(array('COUNT'=>new \Zend\Db\Sql\Expression('COUNT(*)')));
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current()["COUNT"];
	}
	public function CountApplyDataBySearch($whereDatas) {
		$where = new Where();
		$where->isNull("request_date");
		foreach ($whereDatas as $field => $data) {
			if ($field == "name") {
				$where->and->nest()->like("name", "%" . $data . "%")
					->or->like("kana", "%" . $data . "%")->unnest();
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
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current()["COUNT"];
	}
	public function CountRequestDataBySearch($whereDatas) {
		$where = new Where();
		$where->isNotNull("request_date")->and->isNull("execute_date");
		foreach ($whereDatas as $field => $data) {
			if ($field == "name") {
				$where->and->nest()->like("name", "%" . $data . "%")
					->or->like("kana", "%" . $data . "%")->unnest();
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
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current()["COUNT"];
	}
	/*　ここまで　*/

	public function RequestByIdx($idx) {
		$qry = $this->sql->update("record")->where(["idx" => $idx])->set(["request_date" => date("Y-m-d H:i:s")]);
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}
}