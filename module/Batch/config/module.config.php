<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Batch\Controller\Index' => 'Batch\Controller\IndexController',
        ),
    ),

    'console' => array(
        'router' => array(
            'routes' => array(
                'chatting_checker_robots' => array(
                    'options' => array(
                        'route' => 'push <action>', 
                        'defaults' => array(
                            'controller' => 'Batch\Controller\Index',
                        ),
                    ),
                ),
            )
        )
    ),
    
);
