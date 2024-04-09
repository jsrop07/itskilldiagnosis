<?php

$_SERVER['SITE_URL'] = $_SERVER['HTTP_HOST'];

return array(
        'db' => array(
                'driver'         => 'Pdo',
                'dsn'            => 'mysql:dbname=itds_db;host=namecardtest.gngservice.jp',
                'username' => 'root',
                'password' => 'gngs12345',
                'dbname' => 'itds_db',
                'host' => 'namecardtest.gngservice.jp',
                'driver_options' => array(
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                        'buffer_results' => true,
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
        'session' => array(
                'remember_me_seconds' => 2419200,
                'use_cookies'       => true,
                'cookie_httponly'   => false,
                'cookie_lifetime'   => 2419200,
                'gc_maxlifetime'    => 2419200,
                'cookie_domain' => '.gngservice.jp',
        ),
				'smtp' => array(
					'name'              => 'gngs.co.jp',
					'host'              => 'smtp.mail.us-east-1.awsapps.com',
					'port' => 465,
					'fromemail' => 'spredempt@gngs.co.jp',
					'fromname' => 'paku',
					'connection_class' => 'login',
					'connection_config' => array(
							'username' => 'spredempt@gngs.co.jp',
							'password' => '1Corinthians13:13',
							'ssl'=> 'ssl',
					),
				),

);
