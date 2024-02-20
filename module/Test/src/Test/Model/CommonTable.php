<?php

namespace Test\Model;

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

    //指定ユーザーのデータ取得
    public function getUserInfo($p)
    {
        $qry = $this->sql->select("test_user_info")
            ->where(
                array(
                    "user_id" => $p['userid']
                )
            );
        $res = $this->sql->prepareStatementForSqlObject($qry)->execute()->current();
        return $res;

        //where分をこのように作成も可能であるが、基本的に上記の方法通りする。（セキュリティ的に良い）
        //$qry=$this->sql->select("test_user_info")->where("user_id='".$p['userid']."'");

    }

    //データの保存
    public function setUserInfo($p)
    {
        //保存するデータを配列に入れる
        $data = array(
            "user_id" => $p['user_id'],
            "user_pw" => $p['user_pw'],
            "user_name" => $p['user_name'],
            "wdate" => date("Y-m-d H:i:s"),
        );

        //それをinsertする
        $qry = $this->sql->insert("test_user_info")
            ->values($data);
        $this->sql->prepareStatementForSqlObject($qry)->execute();
        //登録したユーザーのidxキーの取得方法↓
        $inserted_idx = $this->adapter->getDriver()->getLastGeneratedValue();
        return $inserted_idx;
    }

    //データの更新
    public function modifyUserInfo($p)
    {
        //修正するデータを配列に入れる
        $data = array(
            "user_name" => $p['user_name'],
            "ldate" => date("Y-m-d H:i:s"),
        );

        //それをinsertする
        $qry = $this->sql->update("test_user_info")
            ->set($data)
            ->where(
                array(
                    "idx" => $p['idx']
                )
            );
        $this->sql->prepareStatementForSqlObject($qry)->execute();
        //登録したユーザーのidxキーの取得方法↓
        $inserted_idx = $this->adapter->getDriver()->getLastGeneratedValue();
        return $inserted_idx;
    }

    //データの削除
    public function removeUserInfo($p)
    {

        $qry = $this->sql->delete("test_user_info")
            ->where(
                array(
                    "user_id" => $p['userid']
                )
            );
        $res = $this->sql->prepareStatementForSqlObject($qry)->execute();
        return $res;
    }
}
