<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
return array(
	"router" => array(
		"routes" => array(
			"main" => array(
				"type" => "Zend\Mvc\Router\Http\Segment",
				"options" => array(
					"route" => "/admin",
					"constraints" => array(),
					"defaults" => array(
						"controller"	=> "Account",
						"action"			=> "index",
					),
				),
			),
			"login" => array(
				"type" => "Zend\Mvc\Router\Http\Segment",
				"options" => array(
					"route" => "/admin/login",
					"constraints" => array(),
					"defaults" => array(
						"controller"	=> "Account",
						"action"			=> "main",
					),
				),
			),
			"account" => array(
				"type" => "Zend\Mvc\Router\Http\Segment",
				"options" => array(
					"route" => "/admin/account[/:action]",
					"constraints" => array(),
					"defaults" => array(
						"controller"	=> "Account",
						"action"			=> "index",
					),
				),
			),
			"question" => array(
				"type" => "Zend\Mvc\Router\Http\Segment",
				"options" => array(
					"route" => "/admin/question[/:action][/:index]",
					"constraints" => array(),
					"defaults" => array(
						"controller"	=> "Question",
						"action"			=> "index",
					),
				),
			),
			"diagnosis" => array(
				"type" => "Zend\Mvc\Router\Http\Segment",
				"options" => array(
					"route" => "/admin/diagnosis[/:action][/:index]",
					"constraints" => array(),
					"defaults" => array(
						"controller"	=> "Diagnosis",
						"action"			=> "index",
					),
				),
			),
			"applicant" => array(
				"type" => "Zend\Mvc\Router\Http\Segment",
				"options" => array(
					"route" => "/admin/applicant[/:action][/:index]",
					"constraints" => array(),
					"defaults" => array(
						"controller"	=> "Applicant",
						"action"			=> "index",
					),
				),
			),
			"manager" => array(
				"type" => "Zend\Mvc\Router\Http\Segment",
				"options" => array(
					"route" => "/admin/manager[/:action][/:index]",
					"constraints" => array(),
					"defaults" => array(
						"controller"	=> "Manager",
						"action"			=> "index",
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
	"controllers" => array(
		"invokables" => array(
			"Account"		=> "Admin\Controller\AccountController",
			"Question"	=> "Admin\Controller\QuestionController",
			"Diagnosis"	=> "Admin\Controller\DiagnosisController",
			"Applicant"		=> "Admin\Controller\ApplicantController",
			"Manager"		=> "Admin\Controller\ManagerController",
		),
	),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => array(
					"layout/default"						=> __DIR__ . "/../view/layout/layout_default.phtml",
					"breadcrumb"						=> __DIR__ . "/../view/layout/breadcrumb.phtml",
					"pagination"						=> __DIR__ . "/../view/layout/pagination.phtml",

					"admin"									=> __DIR__ . "/../view/layout/admin_layout.phtml",
					"layout/user"						=> __DIR__ . "/../view/layout/user/layout.phtml",
					"layout/user/login"			=> __DIR__ . "/../view/layout/user/login.phtml",
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
