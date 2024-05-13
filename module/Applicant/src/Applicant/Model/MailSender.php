<?php

namespace Applicant\Model;

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
use Zend\Mime as Mimes;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;


class MailSender extends AbstractActionController
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
		// if(!isset($params['email'])){
			// $params['email']='webmaster@kishe.com';
		// }
		// if(!isset($params['name'])){
		// 	$params['name']='Member';
		// }

		$params=array(
			'config'=>$params['config'],
			'email'=>$params['email'],
			'name'=>$params['name'],
			'title'=>$mail_title,
			'fromemail'=>$params['config']['smtp']['fromemail'],
			'fromname'=>$params['config']['smtp']['fromname'],
			'content'=>$params['content'],
			// 'attachment'=>$params['attachment'],
			// 'receiver_cc1'=>$params['receiver_cc1'],
			// 'receiver_cc2'=>$params['receiver_cc2'],
			// 'receiver_cc3'=>$params['receiver_cc3'],
			// 'receiver_cc4'=>$params['receiver_cc4'],
			// 'receiver_cc5'=>$params['receiver_cc5'],
			// 'receiver_bcc1'=>$params['receiver_bcc1'],
			// 'receiver_bcc2'=>$params['receiver_bcc2'],
			// 'receiver_bcc3'=>$params['receiver_bcc3'],
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

		// if($params['attachment']!=''){
		// 	$fileContents = fopen($params['attachment'], 'r');
		// 	$attachment = new MimePart($fileContents);
		// 	$expfilename = explode("/",$params['attachment']);

		// 	$attachment->type = 'application/stream';
		// 	$attachment->filename = '=?ISO-2022-JP?B?'.base64_encode(mb_convert_encoding($expfilename[sizeof($expfilename)-1], 'ISO-2022-JP','UTF-8')).'?=';

	  //       $attachment->encoding    = \Zend\Mime\Mime::ENCODING_BASE64;
	  //       $attachment->disposition = \Zend\Mime\Mime::DISPOSITION_ATTACHMENT;
		// 	$body->setParts(array($bodyMessage,$attachment));
		// }else{
		// 	$body->setParts(array($bodyMessage,));
		// }

		$body->setParts(array($bodyMessage,));
		$mail = new Mail\Message();
		$mail->setEncoding('ASCII');
		$mail->setBody($body);
		$mail->setFrom($params['fromemail'],$params['fromname']);
		$mail->setTo($params['email'],'');

		// if($params['receiver_cc1']!=''){
		// 	$mail->addCc($params['receiver_cc1']);
		// }
		// if($params['receiver_cc2']!=''){
		// 	$mail->addCc($params['receiver_cc2']);
		// }
		// if($params['receiver_cc3']!=''){
		// 	$mail->addCc($params['receiver_cc3']);
		// }
		// if($params['receiver_cc4']!=''){
		// 	$mail->addCc($params['receiver_cc4']);
		// }
		// if($params['receiver_cc5']!=''){
		// 	$mail->addCc($params['receiver_cc5']);
		// }
		// if($params['receiver_bcc1']!=''){
		// 	$mail->addBcc($params['receiver_bcc1']);
		// }
		// if($params['receiver_bcc2']!=''){
		// 	$mail->addBcc($params['receiver_bcc2']);
		// }
		// if($params['receiver_bcc3']!=''){
		// 	$mail->addBcc($params['receiver_bcc3']);
		// }


		$mail->setSubject("=?iso-2022-jp?B?".base64_encode(mb_convert_encoding($params['title'],"JIS","UTF-8"))."?=");

		$transport = new SmtpTransport();


		
		// $params['config']['smtp']['jjjj']['password']=
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

