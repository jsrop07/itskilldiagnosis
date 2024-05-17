<?php

namespace Admin\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

class QuestionTable {
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

	public function CreateQuestion($datas) {
		$qry = $this->sql->insert("question")->values($datas);
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	/** Get List
	 * @return Paginator
	*/
	public function GetAllList() {
		$qry = $this->sql->select("question")->where(["date_delete" => null])->order("date_regist desc");
		$paginatorAdapter = new DbSelect($qry, $this->adapter);
		return new Paginator($paginatorAdapter);
	}
	
	/** Get List by Search data 
	 * @param array $whereDatas [index => admin_approve, title]
	 * @return Paginator
	*/
	public function GetListBySearch($whereDatas) {
		$where = new Where();
		$where->isNull("date_delete");
		foreach ($whereDatas as $field => $data) {
			if ($field == "title") {
				$where->and->nest()
					->like("title", "%" . $data . "%")
					->or->like("question", "%" . $data . "%")
				->unnest();
				continue;
			}
			$where->and->equalTo($field, $data);
		}

		$qry = $this->sql->select("question")->where($where)->order("date_regist DESC");
		$paginatorAdapter = new DbSelect($qry, $this->adapter);
		return new Paginator($paginatorAdapter);
	}

	/** Get List by Order data
	 * @param array $orderData [field =>, seq =>]
	 * @return Paginator
	*/
	public function GetListByAlign($orderData) {
		$qry = $this->sql->select("question")->where(["date_delete" => null]);
		switch ($orderData["field"]) {
			case "class1st":
			case "class2nd":
				$qry->join("option", "question." . $orderData["field"] . " = option.idx", array("text_" . $orderData["field"] => "text"), "INNER");
				$qry->order(["text_" . $orderData["field"] => $orderData["seq"], "date_regist" => "DESC"]);
				break;
			case "date_regist":
				$qry->order([$orderData["field"] => $orderData["seq"]]);
				break;
			default:
				$qry->order([$orderData["field"] => $orderData["seq"], "date_regist" => "DESC"]);
				break;
		}
		$paginatorAdapter = new DbSelect($qry, $this->adapter);
		return new Paginator($paginatorAdapter);
	}

	/** Get List by Search&Order data
	 * @param array $whereDatas [index => admin_approve, title]
	 * @param array $orderData [field =>, seq =>]
	 * @return Paginator
	*/
	public function GetListBySearchnAlign($whereDatas, $orderData) {
		$where = new Where();
		$where->isNull("date_delete");
		foreach ($whereDatas as $field => $data) {
			if ($field == "title") {
				$where->and->nest()
					->like("title", "%" . $data . "%")
					->or->like("question", "%" . $data . "%")
				->unnest();
				continue;
			}
			$where->and->equalTo($field, $data);
		}

		$qry = $this->sql->select("question")->where($where);
		switch ($orderData["field"]) {
			case "class1st":
			case "class2nd":
				$qry->join("option", "question." . $orderData["field"] . " = option.idx", array("text_" . $orderData["field"] => "text"), "INNER");
				$qry->order(["text_" . $orderData["field"] => $orderData["seq"], "date_regist" => "DESC"]);
				break;
			case "date_regist":
				$qry->order([$orderData["field"] => $orderData["seq"]]);
				break;
			default:
				$qry->order([$orderData["field"] => $orderData["seq"], "date_regist" => "DESC"]);
				break;
		}
		$paginatorAdapter = new DbSelect($qry, $this->adapter);
		return new Paginator($paginatorAdapter);
	}

	/** Get List by Code
	 * @param string $code admin_code
	 * @return Pagniator
	 */
	public function GetValidList($code) {
		$where = new Where();
		$where->isNull("date_delete")
			->and->nest()
				->isNotNull("date_approve")
				->or->equalTo("admin_regist", $code)
			->unnest();

		$qry = $this->sql->select("question")->where($where)->order("date_regist DESC");
		$paginatorAdapter = new DbSelect($qry, $this->adapter);
		return new Paginator($paginatorAdapter);
	}

	/** Get List by Code, Search data
	 * @param string $code admin_code
	 * @param array $whereDatas [index => admin_approve, title]
	 * @return Pagniator
	*/
	public function GetValidListBySearch($code, $whereDatas) {
		$where = new Where();
		$where->isNull("date_delete")
			->and->nest()
				->isNotNull("date_approve")
				->or->equalTo("admin_regist", $code)
			->unnest();
		foreach ($whereDatas as $field => $data) {
			if ($field == "title") {
				$where->and->like("title", "%" . $data . "%");
				continue;
			}
			$where->and->equalTo($field, $data);
		}

		$qry = $this->sql->select("question")->where($where)->order("date_regist DESC");
		$paginatorAdapter = new DbSelect($qry, $this->adapter);
		return new Paginator($paginatorAdapter);
	}

	/** Get List by Code, Order data
	 * @param string $code admin_code
	 * @param array $orderData [field =>, seq =>]
	 * @return Paginator
	*/
	public function GetValidListByAlign($code, $orderData) {
		$where = new Where();
		$where->isNull("date_delete")
			->and->nest()
				->isNotNull("date_approve")
				->or->equalTo("admin_regist", $code)
			->unnest();

		$qry = $this->sql->select("question")->where($where);
		switch ($orderData["field"]) {
			case "class1st":
			case "class2nd":
				$qry->join("option", "question." . $orderData["field"] . " = option.idx", array("text_" . $orderData["field"] => "text"), "INNER");
				$qry->order(["text_" . $orderData["field"] => $orderData["seq"], "date_regist" => "DESC"]);
				break;
			case "date_regist":
				$qry->order([$orderData["field"] => $orderData["seq"]]);
				break;
			default:
				$qry->order([$orderData["field"] => $orderData["seq"], "date_regist" => "DESC"]);
				break;
		}
		$paginatorAdapter = new DbSelect($qry, $this->adapter);
		return new Paginator($paginatorAdapter);
	}
	
	/** Get List by Code, Search&Order data
	 * @param string $code admin_code
	 * @param array $whereDatas [index => admin_approve, title]
	 * @param array $orderData [field =>, seq =>]
	 * @return Paginator
	*/
	public function GetListValidBySearchnAlign($code, $whereDatas, $orderData) {
		$where = new Where();
		$where->isNull("date_delete")
			->and->nest()
				->isNotNull("date_approve")
				->or->equalTo("admin_regist", $code)
			->unnest();
		foreach ($whereDatas as $field => $data) {
			if ($field == "title") {
				$where->and->like("title", "%" . $data . "%");
				continue;
			}
			$where->and->equalTo($field, $data);
		}

		$qry = $this->sql->select("question")->where($where);
		switch ($orderData["field"]) {
			case "class1st":
			case "class2nd":
				$qry->join("option", "question." . $orderData["field"] . " = option.idx", array("text_" . $orderData["field"] => "text"), "INNER");
				$qry->order(["text_" . $orderData["field"] => $orderData["seq"], "date_regist" => "DESC"]);
				break;
			case "date_regist":
				$qry->order([$orderData["field"] => $orderData["seq"]]);
				break;
			default:
				$qry->order([$orderData["field"] => $orderData["seq"], "date_regist" => "DESC"]);
				break;
		}
		$paginatorAdapter = new DbSelect($qry, $this->adapter);
		return new Paginator($paginatorAdapter);
	}

	/** Read data by index
	 * @param int $idx index
	 * @return mixed data
	 */
	public function ReadByIdx($idx) {
		$qry = $this->sql->select("question")->where(["idx" => $idx]);
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
	}

	/** Update data by index
	 * @param int $idx index
	 * @param mixed $setDatas
	 * @return mixed data
	 */
	public function UpdateByIdx($idx, $setDatas) {
		$qry = $this->sql->update("question")->where(["idx" => $idx])->set($setDatas);
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
	}

	/** Delete data by index
	 * @param int $idx index
	 * @param mixed $setDatasW
	 * @return mixed data
	 */
	public function RemoveQuestion($idx, $setDatas) {
		$setDatas["date_delete"] = date("Y-m-d H:i:s");
		$qry = $this->sql->update("question")->where(["idx" => $idx])->set($setDatas);
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
	}
	
	/** Read List by Search data 
	 * @param array $whereDatas [field => value]
	 * @return array $questionDatas
	*/
	public function ReadForDiagnosis($whereDatas = []) {
		$where = new Where();
		$where->isNotNull("date_approve")->and->isNull("date_delete");
		foreach ($whereDatas as $field => $data) {
			$where->and->equalTo($field, $data);
		}

		$qry = $this->sql->select("question")->where($where)->order("date_regist DESC");
		return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
	}
}
