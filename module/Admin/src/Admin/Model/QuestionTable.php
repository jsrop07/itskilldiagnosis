<?php

namespace Admin\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

class QuestionTable {
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

	public function CreateQuestion($datas) {
		$qry = $this->sql->insert("question")->values($datas);
		try { return $this->sql->prepareStatementForSqlObject($qry)->execute();
		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}

	public function ReadQuestion() {
		$qry = $this->sql->select("question")->order("date_regist DESC");
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	/** Read for list
	 * @return mixed datas
	*/
	public function ReadAllList() {
		$qry = $this->sql->select("question")->where(["date_delete" => null])->order("date_regist DESC");
		try {
			return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}
	public function GetAllList() {
		$qry = $this->sql->select("question")->where(["date_delete" => null])->order("date_regist desc");
		$paginatorAdapter = new DbSelect($qry, $this->adapter);
		return new Paginator($paginatorAdapter);
	}
	
	/** Read for list by search data 
	 * @param mixed $whereDatas array #index => admin_approve, title
	 * @return mixed datas
	*/
	public function ReadListByOption($whereDatas) {
		$where = new Where();
		$where->isNull("date_delete");
		foreach ($whereDatas as $field => $data) {
			if ($field == "title") {
				$where->and->like("title", "%" . $data . "%");
				continue;
			}
			$where->and->equalTo($field, $data);
		}

		$qry = $this->sql->select("question")->where($where)->order("date_regist DESC");
		try {
			return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}
	public function GetListByOption($whereDatas) {
		$where = new Where();
		$where->isNull("date_delete");
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

	/** Read for list by admin_code
	 * @param string $code admin_regist
	 * @return mixed datas
	 */
	public function ReadValidList($code) {
		$where = new Where();
		$where->isNull("date_delete")
			->and->nest()
				->isNotNull("date_approve")
				->or->equalTo("admin_regist", $code)
			->unnest();

		$qry = $this->sql->select("question")->where($where)->order("date_regist DESC");
		try {
			return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}
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

	/** Read for list by admin_code and search data
	 * @param string $code admin_regist
	 * @param mixed $whereDatas array #index => admin_approve, title
	 * @return mixed datas
	*/
	public function ReadValidListByOption($code, $whereDatas) {
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
		try {
			return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}
	public function GetValidListByOption($code, $whereDatas) {
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

	/** Read data by index
	 * @param int $idx index
	 * @return mixed data
	 */
	public function ReadByIdx($idx) {
		$qry = $this->sql->select("question")->where(["idx" => $idx]);
		try {
			return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}

	/** Update data by index
	 * @param int $idx index
	 * @param mixed $setDatas
	 * @return mixed data
	 */
	public function UpdateByIdx($idx, $setDatas) {
		$qry = $this->sql->update("question")->where(["idx" => $idx])->set($setDatas);
		try {
			return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}

	/** Delete data by index
	 * @param int $idx index
	 * @param mixed $setDatas
	 * @return mixed data
	 */
	public function DeleteQuestion($idx, $setDatas) {
		$setDatas["date_delete"] = date("Y-m-d H:i:s");
		$qry = $this->sql->update("question")->where(["idx" => $idx])->set($setDatas);
		try {
			return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}
}
