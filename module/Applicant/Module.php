<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Applicant;

use Applicant\Model\AdminInfoTable;
use Applicant\Model\QuestionTypeTable;
use Applicant\Model\QuestionTable;
use Applicant\Model\ApplicantLoginTable;
use Applicant\Model\ApplicationTable;
use Applicant\Model\ApplicantExamTable;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Session\Container;

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

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
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
                "AppQuestionTable" => function ($sm) {
                    $dbAdapter = $sm->get("Zend\Db\Adapter\Adapter");
                    $table = new QuestionTable($dbAdapter);
                    return $table;
                },
                "ApplicantLoginTable" => function ($sm) {
                    $dbAdapter = $sm->get("Zend\Db\Adapter\Adapter");
                    $table = new ApplicantLoginTable($dbAdapter);
                    return $table;
                },
                "ApplicationTable" => function ($sm){
                    $dbAdapter = $sm->get("Zend\Db\Adapter\Adapter");
                    $table = new ApplicationTable($dbAdapter);
                    return $table;
                },
                "ApplicantExamTable" => function ($sm) {
                    $dbAdapter = $sm->get("Zend\Db\Adapter\Adapter");
                    $table = new ApplicantExamTable($dbAdapter);
                    return $table;
                },
            ),
        );
    }
}