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

class ApplicantLoginTable
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

  public function login($email, $password)
  {
    $qry = $this->sql->select("applicant")->where(
      array(
        "email" => $email,
        "password" => $password,
      )
    );

    $result = $this->sql->prepareStatementForSqlObject($qry)->execute()->current();

    if (empty($result)) {
      $response = array(
        'status' => 'wronginfo',
    );
    return json_encode($response);
    }

    if ($result["password"] == $password) {
      $recordQry = $this->sql->select("record")->where(
        array(
          "applicant_idx" => $result['idx']
        )
      )->order("idx DESC");
    }
    $resultqry = $this->sql->prepareStatementForSqlObject($recordQry)->execute()->current();

    $currentDateTime = date("Y-m-d H:i:s"); // current time
    $dateSchedule = $resultqry['date_schedule']; // diagnosis schedule time 
    $currentDateTimeObj = date_create($currentDateTime); //turn to datetime object by cureenttDateTime 
    $dateScheduleObj = date_create($dateSchedule); // turn to datetime object by dateScheduleTime
    
    $dateInterval = $currentDateTimeObj -> diff($dateScheduleObj); // calculate dateScheduletime - cureentDateTime
    $minutesDifference = ($dateInterval->days * 24 * 60) + ($dateInterval->h * 60) + $dateInterval->i; //turn days, hour, minute to minute

    $dateSchedulePlus = date("Y-m-d H:i:s", strtotime($dateSchedule . ' +30 minutes'));

    if($minutesDifference <= 30 && $currentDateTimeObj > $dateScheduleObj){
      
      $response = array(
        'status' => 'success',
    );
    return json_encode($response);
    } elseif($minutesDifference > 30 && $currentDateTimeObj > $dateScheduleObj){
      $updateQry = $this->sql->update('record')->set(array('rank' => 'F'))->where(array('idx' => $resultqry['idx']));
      $updateResult = $this->sql->prepareStatementForSqlObject($updateQry)->execute();

      $response = array(
        'status' => 'timeout',
        'timein' => $dateSchedule,
        'timeout' => $dateSchedulePlus
      );
    return json_encode($response);
    }
    $response = array(
      'status' => 'wrongtime',
      'timein' => $dateSchedule,
      'timeout' => $dateSchedulePlus
    );
    return json_encode($response);
    exit;
    }

}
