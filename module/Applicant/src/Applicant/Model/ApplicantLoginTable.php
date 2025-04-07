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
    
    $currentDateTimeObj = date_create($currentDateTime); // current time as DateTime object
    $dateScheduleObj = date_create($dateSchedule); // schedule time as DateTime object
    
    // 날짜만 비교 (Y-m-d 형식)
    $currentDate = $currentDateTimeObj->format('Y-m-d');
    $scheduleDate = $dateScheduleObj->format('Y-m-d');
    
    // timeout 기준 시간: 예약 시간 기준 +1일
    $dateSchedulePlus = date("Y-m-d 23:59:59", strtotime($dateSchedule));
    
    if ($currentDate === $scheduleDate) {
        // 같은 날짜면 success
        $response = array(
            'status' => 'success',
        );
        return json_encode($response);
    } else {
        // 날짜가 다르면 timeout 처리
        $updateQry = $this->sql->update('record')->set(array('rank' => 'F'))->where(array('idx' => $resultqry['idx']));
        $updateResult = $this->sql->prepareStatementForSqlObject($updateQry)->execute();
    
        $response = array(
            'status' => 'timeout',
            'timein' => $dateSchedule,
            'timeout' => $dateSchedulePlus
        );
        return json_encode($response);
    }
    
    // 예약일이 오기 전 호출된 경우 (예외 처리)
    $response = array(
        'status' => 'wrongtime',
        'timein' => $dateSchedule,
        'timeout' => $dateSchedulePlus
    );
    return json_encode($response);
    exit;
  }    
}
