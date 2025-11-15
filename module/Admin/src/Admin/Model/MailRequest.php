<?php

namespace Admin\Model;

use Zend\Mvc\Controller\AbstractActionController;
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

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Mvc\Controller\ActionController;

use Zend\Mail;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
// use Zend\Mime as Mimes;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;


class MailRequest extends AbstractActionController
{

	public function __construct()
	{
        $this->config=$this->getConfig();
        $dbArr=$this->config['db'];

		$this->SPEEDCHK=[];
		$this->query_start='';
	}


	public function mailsender($params){
		$mail_title=$params['title'];

		// $params['config']['smtp']['connection_config']['username']=$params['managerEmail'];
		// $params['config']['smtp']['connection_config']['password']=$params['smtp_password'];
		// $params['config']['smtp']['fromemail']=$params['managerEmail'];

		$params=array(
			'config'=>$params['config'],
			'email'=>$params['email'],
			'name'=>$params['name'],
			'title'=>$mail_title,
			'fromemail'=>$params['config']['smtp']['fromemail'],
			'fromname'=>$params['config']['smtp']['fromname'],
			'content'=>$params['content'],
		);

		$viewModel  = new ViewModel();
		$viewModel->setVariables(array(
			'content'  => $params['content'],
		));

		$bodyPart = new \Zend\Mime\Message();
		$bodyMessage    = new \Zend\Mime\Part(mb_convert_encoding($params['content'], 'ISO-2022-JP-MS','UTF-8'));
		$bodyMessage->charset='ISO-2022-JP';
		$bodyMessage->type = "text/plain";

		mb_language("Japanese");
		mb_internal_encoding ("ISO-2022-JP"); 
		$body = new MimeMessage();

	
		$body->setParts(array($bodyMessage,));
		$mail = new Mail\Message();
		$mail->setEncoding('ASCII');
		$mail->setBody($body);
		$fromEmail = trim(str_replace(["\r", "\n"], '', $params['fromemail']));
    	$toEmail   = trim(str_replace(["\r", "\n"], '', $params['email']));
		$fromName = str_replace(["\r", "\n"], '', $fromName);
		$mail->setFrom($fromEmail);      // 이름 없이
    	$mail->setTo($toEmail);          // 받는 사람

		$mail->setSubject("=?iso-2022-jp?B?".base64_encode(mb_convert_encoding($params['title'],"JIS","UTF-8"))."?=");

		$transport = new SmtpTransport();

		unset($params['config']['smtp']['fromname']);
		unset($params['config']['smtp']['fromemail']);
		$options   = new SmtpOptions($params['config']['smtp']);
		$transport->setOptions($options);
		try {
			$transport->send($mail);
		}
		catch (\Exception $e) {
			$result["exception"] = $e->getMessage();
			$result["status"] = "fail";
			return $result;
		}
		

		$ret['transport']=$transport;
		return $ret;
	}

	public function mailAdmin($params){
		$mail_title=$params['title'];

		// $params['config']['smtp']['connection_config']['username']=$params['email'];
		// $params['config']['smtp']['connection_config']['password']=$params['smtp_password'];
		// $params['config']['smtp']['fromemail']=$params['email'];

		$params=array(
			'config'=>$params['config'],
			'email'=>$params['email'],
			'name'=>$params['name'],
			'title'=>$mail_title,
			'fromemail'=>$params['config']['smtp']['fromemail'],
			'fromname'=>$params['config']['smtp']['fromname'],
			'content'=>$params['content'],
		);

		$viewModel  = new ViewModel();
		$viewModel->setVariables(array(
			'content'  => $params['content'],
		));

		$bodyPart = new \Zend\Mime\Message();
		$bodyMessage    = new \Zend\Mime\Part(mb_convert_encoding($params['content'], 'ISO-2022-JP-MS','UTF-8'));
		$bodyMessage->charset='ISO-2022-JP';
		$bodyMessage->type = "text/plain";

		mb_language("Japanese");
		mb_internal_encoding ("ISO-2022-JP"); 
		$body = new MimeMessage();

	
		$body->setParts(array($bodyMessage,));
		$mail = new Mail\Message();
		$mail->setEncoding('ASCII');
		$mail->setBody($body);
		$fromEmail = trim(str_replace(["\r", "\n"], '', $params['fromemail']));
    	$toEmail   = trim(str_replace(["\r", "\n"], '', $params['email']));
		$fromName = str_replace(["\r", "\n"], '', $fromName);
		$mail->setFrom($fromEmail);      // 이름 없이
    	$mail->setTo($toEmail);          // 받는 사람

		$mail->setSubject("=?iso-2022-jp?B?".base64_encode(mb_convert_encoding($params['title'],"JIS","UTF-8"))."?=");

		$transport = new SmtpTransport();

		unset($params['config']['smtp']['fromname']);
		unset($params['config']['smtp']['fromemail']);
		$options   = new SmtpOptions($params['config']['smtp']);
		$transport->setOptions($options);
		$transport->send($mail);

		$ret['transport']=$transport;
		return $ret;
	}


    public function getConfig(){
        if(isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT']!=''){
            $droot = $_SERVER['DOCUMENT_ROOT'];
        }else{
            $droot = "abc";
        }
        if(is_file($droot.'/../config/autoload/local.php')){
            $config = require $droot.'/../config/autoload/local.php';
        }else{
            $config = require $droot.'/../config/autoload/global.php';
        }
        return $config;
    }
}

