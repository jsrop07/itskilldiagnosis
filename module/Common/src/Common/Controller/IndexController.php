<?php
namespace Common\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Session\Container;

class IndexController extends AbstractActionController
{


	public function indexAction()
	{
		//基本設定情報を取得
		$config=$this->getServiceLocator()->get('config');
		//モデル連動
		$tbl=$this->getServiceLocator()->get('CommonTable');
		
		//GETパラメーターを取得
		$q=$this->params()->fromQuery();
		//POSTパラメーターを取得
		$p=$this->params()->fromPost();

		//HTML情報を先に登録（OPTION）
		$metaInfo=array(
			"title"=>("研修サイト"),
			"description"=>("研修サイトです。前回PHPのみで作成したサイトをこちらに追加する。"),
			"keywords"=>("研修サイト、テストサイトなど")
		);
		$this->layout()->setVariables($metaInfo);

		//レイアウトファイルの設定（レイアウトは画面の上下側の共通に表示される部分）
		$this->layout('layout/goms');

		//HTMLモデル（APIの場合 new JsonModel();を呼び出して、JSONタイプでリターン出来る）
		$vm=new ViewModel();

		//ログイン用セッションの設定
        $session = new Container('ankenInfo');

        //Queryにuseridが入った場合
        if(isset($p['userid'])){
			if($p['userid']!=''){

				//該当userid変数でテーブルからユーザー情報を取得
				$ud = $tbl->getUserInfo($p);

				//パスワードが一致するかを確認
	        	$login = true;
				if(is_array($ud)){
			        if ($p['userpw'] == $ud['user_pw']) {
			        	//一致したらセッション情報にuseridを設定
			            $session->offsetSet('userid',$p['userid']);
			        }else{
			        	$login = false;
			        }
			    }else{
		        	$login = false;
			    }

			    if($login == false){
		        	//一致しない場合エラーメッセージをリターン
		            header("Content-Type: text/html; charset=UTF-8");
		            echo "<script type='text/javascript'>alert('ユーザー情報を確認できません。もう一度入力してください。');history.go(-1);</script>";
		            exit;
		        }
			}
		}

		//Query「ACT変数]にログアウトが入った場合
		if(isset($q['act'])){
			if($q['act']=='logout'){
				//セッション情報からuseridを空白にする
	            $session->offsetSet('userid','');
	            header("Location: /");
	            exit;
			}
		}

		return $vm;
	}





}
