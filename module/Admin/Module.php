<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Admin;

use Admin\Model\AdminInfoTable;
use Admin\Model\AdminTable;
use Admin\Model\QuestionTable;
use Admin\Model\QuestionTypeTable;
use Admin\Model\QuestionPoolTable;
use Admin\Model\OptionTable;
use Admin\Model\ExamTable;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

//use Common\Model\CommonTable;


class Module
{

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
                'CommonTable' =>  function ($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    //$table = new CommonTable($dbAdapter);
                    //return $table;
                },
                "AdminInfoTable" => function ($sm) {
                    $dbAdapter = $sm->get("Zend\Db\Adapter\Adapter");
                    $table = new AdminInfoTable($dbAdapter);
                    return $table;
                },
                "QuestionTypeTable" => function ($sm) {
                    $dbAdapter = $sm->get("Zend\Db\Adapter\Adapter");
                    $table = new QuestionTypeTable($dbAdapter);
                    return $table;
                },
                "QuestionPoolTable" => function ($sm) {
                    $dbAdapter = $sm->get("Zend\Db\Adapter\Adapter");
                    $table = new QuestionPoolTable($dbAdapter);
                    return $table;
                },
                "ExamTable" => function ($sm) {
                    $dbAdapter = $sm->get("Zend\Db\Adapter\Adapter");
                    $table = new ExamTable($dbAdapter);
                    return $table;
                },
			),
		);
	}
}