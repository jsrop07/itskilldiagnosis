<?php

$_SERVER['SITE_URL'] = $_SERVER['HTTP_HOST'];

return array(
	"db" => array(
		"driver"         => "Pdo",
		"dsn"            => "mysql:dbname=itskilldiagnosis;host=localhost",
		"username" => "root",
		"password" => "1234",
		"dbname" => "itskilldiagnosis",
		"host" => "localhost",
		"driver_options" => array(
			PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
			"buffer_results" => true,
		),
	),
	'user-url'=> array(
		'applicant' => 'https://diagnosisbeta.goms.jp/applicant',
		'admin' => 'https://diagnosisbeta.goms.jp/admin',
	),
        'service_manager' => array(
                'factories' => array(
                        'Zend\Db\Adapter\Adapter'
                        => 'Zend\Db\Adapter\AdapterServiceFactory',
                ),
                'invokables' => array(
                        'Zend\Authentication\AuthenticationService' => 'Zend\Authentication\AuthenticationService',
                ),
        ),
		'smtp' => array(
			'name'              => 'gmail.com',
		    'host'              => 'smtp.gmail.com',
			'port' => 587,
			'fromname' => '',
			'connection_class' => 'login',
			'username' => 'jsrop07@gmail.com',      // Gmail 주소
			'password' => 'mvkp gsss rrse oyto',  // 앱 비밀번호
			'ssl'      => 'tls',  
		),

    'session' => array(
      'remember_me_seconds' => 2419200,
      'use_cookies'       => true,
      'cookie_httponly'   => false,
      'cookie_lifetime'   => 2419200,
      'gc_maxlifetime'    => 2419200,
      'cookie_domain' => 'diagnosisbeta.goms.jp',
),

);
