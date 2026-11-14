<?php

return [
    'title'              => 'الوظيفة',
    'model-label'        => 'وظيفة شاغرة',
    'plural-model-label' => 'وظائف شاغرة',

    'navigation' => [
        'title' => 'الوظائف',
    ],

    'table' => [
        'columns' => [
            'name'         => 'الاسم',
            'manager-name' => 'المدير',
            'company-name' => 'الشركة',
        ],

        'actions' => [
            'applications' => [
                'new-applications' => ':count طلبات جديدة',
            ],

            'to-recruitment' => [
                'to-recruitment' => ':count للتوظيف',
            ],

            'total-application' => [
                'total-application' => ':count طلبات',
            ],
        ],
    ],

];
