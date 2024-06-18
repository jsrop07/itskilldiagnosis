<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Applicant;

use Applicant\Model\QuestionTable;
use Applicant\Model\ApplicantLoginTable;
use Applicant\Model\ApplicationTable;
use Applicant\Model\ApplicantExamTable;
use Applicant\Model\MailSender;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Session\Container;

//use Common\Model\CommonTable;


class Module
{

    public function onBootstrap(MvcEvent $e)
    {
        $serviceManager     = $e->getApplication()->getServiceManager();
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        $this->initDbSession( $e );

    }


    private function initDbSession( MvcEvent $e )
    {
        // grab the config array
        $serviceManager     = $e->getApplication()->getServiceManager();
        $config             = $serviceManager->get('config');

        $dbAdapter          = $serviceManager->get('Zend\Db\Adapter\Adapter');
        $sessionOptions = new \Zend\Session\SaveHandler\DbTableGatewayOptions( null );

        $sessionTableGateway = new \Zend\Db\TableGateway\TableGateway('session', $dbAdapter);
        $saveHandler = new \Zend\Session\SaveHandler\DbTableGateway($sessionTableGateway, $sessionOptions);

        $sessionConfig = new \Zend\Session\Config\SessionConfig();
        $sessionConfig->setOptions($config['session']);

        $sessionManager = new \Zend\Session\SessionManager( $sessionConfig , NULL, $saveHandler );
        $sessionManager->start();

        \Zend\Session\Container::setDefaultManager($sessionManager);
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
                "ApplicantMailSenderTable" => function ($sm) {
                    $dbAdapter = $sm->get("Zend\Db\Adapter\Adapter");
                    $table = new MailSender($dbAdapter);
                    return $table;
                },
            ),
        );
    }
}