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

class ApplicantExamTable
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

  // SEOKWON CODE
  public function readById($email)
  {
    $qry = $this->sql->select("applicant")->where(["email" => $email]);
    return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
  }

  public function readByApplicantIdx($applicant_idx)
  {
    $qry = $this->sql->select("record")->where(["applicant_idx" => $applicant_idx])->order("apply_date DESC");
    return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
  }

  public function readByDiagnosisCode($code)
  {
    $qry = $this->sql->select("diagnosis")->where(["code" => $code]);
    return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
  }

  public function readByQuestion($p)
  {
      $select = $this->sql->select('question');
      $select->columns([
          'idx',
          'point',
          'question',
          'question_ko',
          'answer1',
          'answer1_ko',
          'answer2',
          'answer2_ko',
          'answer3',
          'answer3_ko',
          'answer4',
          'answer4_ko',
          'answer5',
          'answer5_ko',
          'correct'
          // 'wdate' => new Expression("DATE_FORMAT(wdate, '%Y-%m-%d %H:%i')")
      ]);

      if (!empty($p['idx'])) {
          $select->where(['idx' => $p['idx']]);
      }
  
      $statement = $this->sql->prepareStatementForSqlObject($select);
      $result = $statement->execute();
  
      $resultSet = new ResultSet();
      $resultSet->initialize($result);
      $resultSet->buffer(); 
      
      return $resultSet;
  }
  public function executeExam($idx){
    $qry=new sql($this->adapter);
    $update=$qry->update('record');
    
    $update->set(['execute_date' => date("Y-m-d H:i:s")])->where(['idx' => $idx]);

    $sqlString = $qry->getSqlStringForSqlObject($update);

    $result = $this->adapter->query($sqlString, Adapter::QUERY_MODE_EXECUTE);
    return $result;
  }

  public function updateExam($sqlWhere, $sqlSet){
    $qry=new sql($this->adapter);
    $update=$qry->update('record');

    // $sqlSet["execute_date"] = date("Y-m-d H:i:s");

    $update->set($sqlSet);
    $update->where($sqlWhere);

    $sqlString = $qry->getSqlStringForSqlObject($update);
    $result = $this->adapter->query($sqlString, Adapter::QUERY_MODE_EXECUTE);

    return $result;   
  }

  public function deletePasswordByIdx($idx){
    $qry = new sql($this->adapter);
    $query = $qry->update('applicant');
    $query->set(['password' => NULL]);
    $query->where(['idx' => $idx]);

    
    $sqlString = $qry->getSqlStringForSqlObject($query);
    $result = $this->adapter->query($sqlString, Adapter::QUERY_MODE_EXECUTE);

    return $result;   
}

public function readByManagerInfo()
{
  $qry = $this->sql->select("admin")->where(["pic" => "y"]);
  return $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
}
}
