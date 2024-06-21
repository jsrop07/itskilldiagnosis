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

public function readById($idx)
{
	$qry = $this->sql->select("applicant")->where(["idx" => $idx]);
	return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
}

public function readByapplicantId($idx)
{
	$qry = $this->sql->select("applicant")->where(["idx" => $idx]);
	return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
}
public function ReadDiagnosis($class2ndAjax, $levelAjax)
{
		$qry = $this->sql->select("diagnosis")->where(
				array(
						"class2nd" => $class2ndAjax,
						"level" => $levelAjax
				)
		)->where("date_end IS NULL");

	$resultSet  = $this->sql->prepareStatementForSqlObject($qry)->execute();

	$results = iterator_to_array($resultSet, false);

	if (empty($results)) {
		return [];
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

	if($recordSet["mail_delay"] == ""){
		$recordSet["mail_delay"] = null;
	}
	$recordSet["request_date"] = date("Y-m-d H:i:s");
	$recordSet["diagnosis_date"] = date("Y-m-d H:i:s");
	// $recordSet["date_mail"] = date("Y-m-d H:i:s");

	$update->set($recordSet);
	$update->where($recordlWhere);

	$sqlString = $qry->getSqlStringForSqlObject($update);

	try {$result = $this->adapter->query($sqlString, Adapter::QUERY_MODE_EXECUTE);} 
	catch (\Exception $e) {echo 'Caught exception: ',  $e->getMessage(), "\n";}	

	return $result;   
	}
	
	public function updateApplicantInfo($applicantWhere, $applicantSet){
	if (!isset($applicantSet['gender']) || $applicantSet['gender'] === '') {
		$applicantSet['gender'] = null;
	}
	if (!isset($applicantSet['career']) || $applicantSet['career'] === '') {
		$applicantSet['career'] = null;
	}
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

	public function saveRecordInfo($recordlWhere, $recordSet){
	// if (!isset($recordSet['class2nd']) || $recordSet['class2nd'] === '') {
	// 	$recordSet['class1st'] = "その他";
	// 	$recordSet['class2nd'] = "33";
	// }
	$qry=new sql($this->adapter);
	$update=$qry->update('record');
	if($recordSet["mail_delay"] == ""){
		$recordSet["mail_delay"] = null;
	}
	if($recordSet["diagnosis_code"] != NULL){
		$recordSet["diagnosis_date"] = date("Y-m-d H:i:s");
	}else if ($recordSet["diagnosis_code"] == ""){
		$recordSet['diagnosis_date'] = NULL;
	}



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
	public function saveApplicantInfo($applicantWhere, $applicantSet){
	if (!isset($applicantSet['gender']) || $applicantSet['gender'] === '') {
		$applicantSet['gender'] = null;
	}
	if (!isset($applicantSet['career']) || $applicantSet['career'] === '') {
		$applicantSet['career'] = null;
	}
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

	public function getApplicantByEmail($email) {
			$qry = new Sql($this->adapter);
			$select = $qry->select('applicant');
			$select->where(['email' => $email]);
			$selectSqlString = $qry->getSqlStringForSqlObject($select);
			$result = $this->adapter->query($selectSqlString, Adapter::QUERY_MODE_EXECUTE);

			return $result->current();
	}

public function getRecord(){
			$qry = $this->sql->select("record")->order("idx DESC");
			return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
	}

	public function insertAndUpdateApplication($dataArray) {
		$qry = new Sql($this->adapter);
		$existingRecord = $this->getApplicantByEmail($dataArray['email']);

		if ($existingRecord) {
			$setDatas = array(
				'password' => $dataArray['password'],
				'name' => $dataArray['name'],
				'kana' => $dataArray['kana'],
				'gender' => isset($dataArray['gender']) ? $dataArray['gender'] : null,                
				'birth' => $dataArray['birth'],
				'career' => isset($dataArray['career']) && $dataArray['career'] !== '' ? $dataArray['career'] : null,
				'certificates' => $dataArray['certificates'],
				'other' => $dataArray['other'],
				'apply_date' => date("Y-m-d H:i:s")
			);
			$sql = $qry->update('applicant')->where(['email' => $dataArray['email']])->set($setDatas);
			$sqlString = $qry->getSqlStringForSqlObject($sql);

			try { $result = $this->adapter->query($sqlString, Adapter::QUERY_MODE_EXECUTE); }
			catch (\Exception $e) { echo 'Caught exception: ',  $e->getMessage(), "\n"; }

			$applicant_idx = $existingRecord['idx'];

		} else {
			$applicantInsert = $qry->insert('applicant');
			$applicantInsert->values([
				'password' => $dataArray['password'],
				'email' => $dataArray['email'],
				'name' => $dataArray['name'],
				'kana' => $dataArray['kana'],
				'gender' => isset($dataArray['gender']) ? $dataArray['gender'] : null,
				'birth' => $dataArray['birth'],
				'career' => isset($dataArray['career']) && $dataArray['career'] !== '' ? $dataArray['career'] : null,
				'certificates' => $dataArray['certificates'],
				'other' => $dataArray['other'],
				'apply_date' => date("Y-m-d H:i:s")
			]);
		
			$applicantSqlString = $qry->getSqlStringForSqlObject($applicantInsert);
		
			try { $result = $this->adapter->query($applicantSqlString, Adapter::QUERY_MODE_EXECUTE); } 
			catch (\Exception $e) { echo 'Caught exception: ',  $e->getMessage(), "\n"; }	
			$applicant_idx = $this->adapter->getDriver()->getLastGeneratedValue();
		}

		$values = array(
			'apply_date' => date("Y-m-d H:i:s"),
			// 'diagnosis_date' => date("Y-m-d H:i:s"),
			'case' => $dataArray['case'],
			'education' => $dataArray['education'],
			'major' => $dataArray['major'],
			'skill' => $dataArray['skill'],
			'class1st' => $dataArray['class1st'],
			'class2nd' => $dataArray['class2nd'],
			'diagnosis_code' => $dataArray['code'],
			'method' => $dataArray['method'],
			'language' => $dataArray['language'],
			'date_schedule' => $dataArray['schedule'],
			'applicant_idx' => $applicant_idx 
		);
		if ($dataArray['mail_delay'] != "") {
			$values['mail_delay'] = $dataArray['mail_delay'];
		}
		if($dataArray['code'] !== ""){
			$values['diagnosis_date'] = date("Y-m-d H:i:s");
		}

		if (!isset($dataArray['save'])) {
			$values['request_date'] = date("Y-m-d H:i:s");
		}

		$recordInsert = $qry->insert('record');
		$recordInsert->values($values);

		$recordSqlString = $qry->getSqlStringForSqlObject($recordInsert);
		try {$result = $this->adapter->query($recordSqlString, Adapter::QUERY_MODE_EXECUTE);}
		catch (\Exception $e) {echo 'Caught exception: ',  $e->getMessage(), "\n";}	

		return $result;   
	}
}
