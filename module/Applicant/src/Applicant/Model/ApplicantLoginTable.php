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
      return "wrong info";
    }

    if ($result["password"] == $password) {
      return "success";
      exit;
    }
    // return "wrong password";
  }

}
