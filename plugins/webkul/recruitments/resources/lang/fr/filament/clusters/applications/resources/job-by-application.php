<?php

return [
    'title'              => 'Poste',
    'model-label'        => 'Poste de travail',
    'plural-model-label' => 'Postes de travail',

    'navigation' => [
        'title' => 'Postes',
    ],

    'table' => [
        'columns' => [
            'name'         => 'Nom',
            'manager-name' => 'Responsable',
            'company-name' => 'Société',
        ],

        'actions' => [
            'applications' => [
                'new-applications' => ':count Nouvelles candidatures',
            ],

            'to-recruitment' => [
                'to-recruitment' => ':count À recruter',
            ],

            'total-application' => [
                'total-application' => ':count Candidatures',
            ],
        ],
    ],

];
