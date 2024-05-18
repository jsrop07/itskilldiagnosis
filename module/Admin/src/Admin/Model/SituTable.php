<?php

namespace Admin\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;

class SituTable
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

	public function ReadByIdx($idx) {
		$qry = $this->sql->select("option")->where(["idx" => $idx]);
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
	}

	public function ReadByText($text) {
		$qry = $this->sql->select("option")->where(["text" => $text]);
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
	}

	public function ReadOption($whereData) {
		$qry = $this->sql->select("option")->where($whereData);
		return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
	}
	
	public function readByManagerInfo()
    {
      $qry = $this->sql->select("admin")->where(["pic" => "y"]);
      return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
    }

	public function readById($email)
	{
	  $qry = $this->sql->select("applicant")->where(["email" => $email]);
	  return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
	}

	public function ReadDiagnosis($class2ndAjax,$levelAjax)
	{
		$qry = $this->sql->select("diagnosis")->where(
			array(
			  "class2nd" => $class2ndAjax,
			  "level" => $levelAjax,
			)
		  );

		  $resultSet  = $this->sql->prepareStatementForSqlObject($qry)->execute();

		  $results = iterator_to_array($resultSet,false);

		  if (empty($results)) {
			return "wrong";
		} else {
			$response = [];
			foreach ($results as $result) {
				$response[] = [
					"code" => $result["code"],
					"title" => $result['title'],
					"question_num" => $result["question_num"]
				];
			}
			return $response;
		}

	}
	
	public function getclass1st(){
        $qry = $this->sql->select("option");
        $qry->columns([
            'idx','type','text'
        ]);
        $qry->where(['type' => 'class1st']);
        $statement = $this->sql->prepareStatementForSqlObject($qry);
        $result = $statement->execute();

        return $result;
    }
	public function getclass2nd(){
        $qry = $this->sql->select("option");
        $qry->columns([
            'idx','type','text','class_upper'
        ]);
        $qry->where(['type' => 'class2nd']);
        $statement = $this->sql->prepareStatementForSqlObject($qry);
        $result = $statement->execute();
    
        return $result;
    }
	public function updateRecordInfo($recordlWhere, $recordSet){
		$qry=new sql($this->adapter);
		$update=$qry->update('record');
	
		$recordSet["request_date"] = date("Y-m-d H:i:s");
	
		$update->set($recordSet);
		$update->where($recordlWhere);

		$sqlString = $qry->getSqlStringForSqlObject($update);

		// $result = $this->adapter->query($sqlString, Adapter::QUERY_MODE_EXECUTE);
		try {
			$result = $this->adapter->query($sqlString, Adapter::QUERY_MODE_EXECUTE);
		} catch (\Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}	

		return $result;   
	  }
	  public function updateApplicantInfo($applicantWhere, $applicantSet){
		$qry=new sql($this->adapter);
		$update=$qry->update('applicant');
	
	
		$update->set($applicantSet);
		$update->where($applicantWhere);
	
		$sqlString = $qry->getSqlStringForSqlObject($update);
		try {
			$result = $this->adapter->query($sqlString, Adapter::QUERY_MODE_EXECUTE);
		} catch (\Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
		}	

		return $result;   
	  }
}
