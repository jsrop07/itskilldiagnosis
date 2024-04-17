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

    public function getQuestionType(){
        $qry = $this->sql->select("option");
        $qry->columns([
            'type','texts'
        ]);
        $qry->where(['type' => 'class2nd']); // WHERE 절 추가
        $statement = $this->sql->prepareStatementForSqlObject($qry);
        $result = $statement->execute();

        return $result;
    }

    public function getDevelop(){
        $qry = $this->sql->select("option");
        $qry->columns([
            'type','texts'
        ]);
        $qry->where(['type' => 'class1st']); // WHERE 절 추가
        $statement = $this->sql->prepareStatementForSqlObject($qry);
        $result = $statement->execute();

        return $result;
    }

    public function insertApplication($dataArray){
        $qry = new Sql($this->adapter);
        $insert = $qry->insert('applicant');
        $insert->values([
            'email'=>$dataArray['email'],
            'name'=>$dataArray['name'],
            'kana'=>$dataArray['kana'],
            'gender'=>$dataArray['gender'],
            'application_category'=>$dataArray['application_category'],
            'education'=>$dataArray['education'],
            'major'=>$dataArray['major'],
            'skill'=>$dataArray['skill'],
            'question_type'=>$dataArray['question_type'],
            'develop'=>$dataArray['develop'],
            'career'=>$dataArray['career'],
            'certificates'=>$dataArray['certificates'],
            'other'=>$dataArray['other'],
            'write_date' => date("Y-m-d H:i:s")
        ]);
        $sqlString = $qry->getSqlStringForSqlObject($insert);
        $result = $this->adapter->query($sqlString, Adapter::QUERY_MODE_EXECUTE);

        return $result;    
    }


}