<?php

$_SERVER['SITE_URL'] = $_SERVER['HTTP_HOST'];

return array(
	"db" => array(
		"driver"         => "Pdo",
		"dsn"            => "mysql:dbname=itds_db;host=18.181.4.65",
		"username" => "root",
		"password" => "gngs1234",
		"dbname" => "itds_db",
		"host" => "18.181.4.65",
		"driver_options" => array(
			PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
			"buffer_results" => true,
		),
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
		// 'smtp' => array(
		// 	'name'              => 'gngs.co.jp',
		// 	'host'              => 'smtp.mail.us-east-1.awsapps.com',
		// 	'port' => 465,
		// 	'fromemail' => 'jsrop07@gmail.co.com',
		// 	'fromname' => 'sw',
		// 	'connection_class' => 'login',
		// 	'connection_config' => array(
		// 			'username' => 'spredempt@gngs.co.jp',
		// 			'password' => '1Corinthians13:13',
		// 			'ssl'=> 'ssl',
		// 	),
		// ),


);
