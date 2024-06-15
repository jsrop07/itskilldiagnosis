<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Admin;

use Admin\Model\AdminTable;
use Admin\Model\QuestionTable;
use Admin\Model\OptionTable;
use Admin\Model\DiagnosisTable;
use Admin\Model\ApplicantTable;
use Admin\Model\RecordTable;
use Admin\Model\SituTable;
use Admin\Model\MailRequest;

use Zend\Mvc\ModuleRouteListener;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Mvc\MvcEvent;

class Module {
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
	
	public function getServiceConfig() {
		return array(
			"factories" => array(
				"AdminTable" => function ($sm) {
					$dbAdapter = $sm->get("Zend\Db\Adapter\Adapter");
					$table = new AdminTable($dbAdapter);
					return $table;
				},
				"QuestionTable" => function ($sm) {
					$dbAdapter = $sm->get("Zend\Db\Adapter\Adapter");
					$table = new QuestionTable($dbAdapter);
					return $table;
				},
				"OptionTable" => function ($sm) {
					$dbAdapter = $sm->get("Zend\Db\Adapter\Adapter");
					$table = new OptionTable($dbAdapter);
					return $table;
				},
				"DiagnosisTable-Admin" => function($sm) {
					$dbAdapter = $sm->get("Zend\Db\Adapter\Adapter");
					$table = new DiagnosisTable($dbAdapter);
					return $table;
				},
				"ApplicantTable-Admin" => function($sm) {
					$dbAdapter = $sm->get("Zend\Db\Adapter\Adapter");
					$table = new ApplicantTable($dbAdapter);
					return $table;
				},
				"SituTable" => function($sm) {
					$dbAdapter = $sm->get("Zend\Db\Adapter\Adapter");
					$table = new SituTable($dbAdapter);
					return $table;
				},
				"RecordTable-Admin" => function($sm) {
					$dbAdapter = $sm->get("Zend\Db\Adapter\Adapter");
					$table = new RecordTable($dbAdapter);
					return $table;
				},
				"MailRequest" => function ($sm) {
                    $dbAdapter = $sm->get("Zend\Db\Adapter\Adapter");
                    $table = new MailRequest($dbAdapter);
                    return $table;
                },
				/*
					作成：朴昰成
					作成日：24/05/13
				*/
				"RecordTable-Admin" => function($sm) {
					$dbAdapter = $sm->get("Zend\Db\Adapter\Adapter");
					$table = new RecordTable($dbAdapter);
					return $table;
				},
				/* ここまで */
			),
		);
	}
}