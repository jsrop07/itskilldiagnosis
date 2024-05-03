<?php

namespace Admin\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

class AdminTable
{
	public function __construct()
	{
		//Local設定ファイルがある場合、Local設定を優先する
		if (is_file($_SERVER['DOCUMENT_ROOT'] . '/../config/autoload/local.php')) {
			$this->config = require $_SERVER['DOCUMENT_ROOT'] . '/../config/autoload/local.php';
		} else {
			$this->config = require $_SERVER['DOCUMENT_ROOT'] . '/../config/autoload/global.php';
		}
		//指定DB設定情報通り接続
		$dbArr = $this->config['db'];
		$adapter = new Adapter($dbArr);
		//Adapter設定
		$this->adapter = $adapter;
		//簡単に共通Sql宣言
		$this->sql = new Sql($this->adapter);
	}

	/** Read for list
	 * @return mixed datas
	*/
	public function ReadAllList() {
		$qry = $this->sql->select("admin")->where(["date_end" => null]);
		try {
			return iterator_to_array($this->sql->prepareStatementForSqlObject($qry)->execute());
		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}
	public function GetAllList() {
		$qry = $this->sql->select("admin")->where(["date_end" => null]);
		$paginatorAdapter = new DbSelect($qry, $this->adapter);
		return new Paginator($paginatorAdapter);
	}

	/** Read Table data By Id
	 * @param mixed $id input id
	 * @return mixed Record Array
	 */
	public function ReadById($id)
	{
		$qry = $this->sql->select("admin")->where(["id" => $id, "date_end" => null]);
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
	}

	/** Read Table data By Id
	 * @param mixed $id input id
	 * @return mixed Record Array
	 */
	public function ReadByCode($code)
	{
		$qry = $this->sql->select("admin")->where(["date_end" => null, "code" => $code]);
		try {
			return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}

	/** Read Table data By Name
	 * @param mixed $name input name
	 * @return mixed Record Array
	 */
	public function ReadByName($name)
	{
		$qry = $this->sql->select("admin")->where(["date_end" => null, "name" => $name]);
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
	}

	/** Read Approvers */
	public function ReadApprovers()
	{
		$qry = $this->sql->select("admin")->where(["date_end" => null, "level > 0"]);
		return $this->sql->prepareStatementForSqlObject($qry)->execute();
	}

	/** Update date_login data To Current time By Code */
	public function UpdateDateLogin($code)
	{
		$qry = $this->sql->update("admin")
			->where(["code" => $code, "date_end" => null])
			->set(["date_login" => date("Y-m-d H:i:s")]);
		$this->sql->prepareStatementForSqlObject($qry)->execute();
	}
}
