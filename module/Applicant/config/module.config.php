<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
return array(
    'router' => array(
        'routes' => array(
            "applicant" => array(
                'type' => 'Zend\Mvc\Router\Http\Segment',
                'options' => array(
                    'route'    => "/applicant[/:action][/:url]",
                    'constraints' => array(),
                    'defaults' => array(
                        "controller" => "Applicant",
                        'action'     => 'index',
                    ),
                ),
            ),
        ),
    ),
    'service_manager' => array(
        'abstract_factories' => array(
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        ),
        'aliases' => array(
            'translator' => 'MvcTranslator',
        ),
    ),
    'translator' => array(
        'locale' => 'en_US',
        'translation_file_patterns' => array(
            array(
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
            ),
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            // "Admin" => "Applicant\Controller\AdminController",
            "Applicant" => "Applicant\Controller\ApplicantController",
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => array(
					"admin"									=> __DIR__ . "/../view/layout/admin_layout.phtml",
					"layout/user"						=> __DIR__ . "/../view/layout/user/layout.phtml",
					"layout/user/login"			=> __DIR__ . "/../view/layout/user/login.phtml",
					"breadcrumb"						=> __DIR__ . "/../view/layout/breadcrumb.phtml",
					"pagination"						=> __DIR__ . "/../view/layout/pagination.phtml",
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'layout/mylayout'           => __DIR__ . '/../view/layout/mylayout.phtml',
            "layout/exam_layout" => __DIR__ . "/../view/layout/exam_layout.phtml",
            "layout/none" => __DIR__ . "/../view/layout/none_layout.phtml",
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
        'strategies' => array(
            'ViewJsonStrategy',
        ),
    ),
    // Placeholder for console routes
    'console' => array(
        'router' => array(
            'routes' => array(),
        ),
    ),
);
