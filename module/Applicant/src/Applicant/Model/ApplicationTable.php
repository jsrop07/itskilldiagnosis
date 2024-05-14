<?php

namespace Applicant\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Sql;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Session\Container;

class ApplicationTable 
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
        $qry->where(['type' => 'class2nd','class_upper'=>7]);
        $statement = $this->sql->prepareStatementForSqlObject($qry);
        $result = $statement->execute();
    
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

    public function insertAndUpdateApplication($dataArray){
        $qry = new Sql($this->adapter);
    
        $existingRecord = $this->getApplicantByEmail($dataArray['email']);
        
        if ($existingRecord) {
            $applicantUpdate = $qry->update('applicant');
            $applicantUpdate->set([
                'name' => $dataArray['name'],
                'kana' => $dataArray['kana'],
                'gender' => $dataArray['gender'],
                'birth' => $dataArray['birth'],
                'career' => $dataArray['career'],
                'certificates' => $dataArray['certificates'],
                'other' => $dataArray['other'],
                'apply_date' => date("Y-m-d H:i:s")
            ]);
            $applicantUpdate->where(['email' => $dataArray['email']]);
            $applicantSqlString = $qry->getSqlStringForSqlObject($applicantUpdate);
            $this->adapter->query($applicantSqlString, Adapter::QUERY_MODE_EXECUTE);
            
            $applicant_idx = $existingRecord['idx'];
        } else {
            $applicantInsert = $qry->insert('applicant');
            $applicantInsert->values([
                'email' => $dataArray['email'],
                'name' => $dataArray['name'],
                'kana' => $dataArray['kana'],
                'gender' => $dataArray['gender'],
                'birth' => $dataArray['birth'],
                'career' => $dataArray['career'],
                'certificates' => $dataArray['certificates'],
                'other' => $dataArray['other'],
                'apply_date' => date("Y-m-d H:i:s")
            ]);
            $applicantSqlString = $qry->getSqlStringForSqlObject($applicantInsert);
            $this->adapter->query($applicantSqlString, Adapter::QUERY_MODE_EXECUTE);
            
            $applicant_idx = $this->adapter->getDriver()->getLastGeneratedValue();
        }
        
        $recordInsert = $qry->insert('record');
        $recordInsert->values([
            'apply_date' => date("Y-m-d H:i:s"),
            'case' => $dataArray['case'],
            'education' => $dataArray['education'],
            'major' => $dataArray['major'],
            'skill' => $dataArray['skill'],
            'class1st' => $dataArray['class1st'],
            'class2nd' => $dataArray['class2nd'],
            'applicant_idx' => $applicant_idx 
        ]);
        $recordSqlString = $qry->getSqlStringForSqlObject($recordInsert);
        $recordResult = $this->adapter->query($recordSqlString, Adapter::QUERY_MODE_EXECUTE);
    }

    public function readByManagerInfo()
    {
      $qry = $this->sql->select("admin")->where(["pic" => "y"]);
      return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
    }
  

}