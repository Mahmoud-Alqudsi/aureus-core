<?php

return [
    'title'              => 'Cargo',
    'model-label'        => 'Cargo',
    'plural-model-label' => 'Cargos',

    'navigation' => [
        'title' => 'Cargos',
    ],

    'table' => [
        'columns' => [
            'name'         => 'Nome',
            'manager-name' => 'Gerente',
            'company-name' => 'Empresa',
        ],

        'actions' => [
            'applications' => [
                'new-applications' => ':count novas candidaturas',
            ],

            'to-recruitment' => [
                'to-recruitment' => ':count para recrutamento',
            ],

            'total-application' => [
                'total-application' => ':count candidaturas',
            ],
        ],
    ],

];
