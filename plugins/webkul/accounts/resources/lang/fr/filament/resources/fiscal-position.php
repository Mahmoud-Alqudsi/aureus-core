<?php

return [
    'global-search' => [
        'zip-from' => 'Code postal de',
        'zip-to'   => 'Code postal à',
        'name'     => 'Nom',
    ],

    'form' => [
        'fields' => [
            'name'                   => 'Nom',
            'foreign-vat'            => 'TVA étrangère',
            'country'                => 'Pays',
            'country-group'          => 'Groupe de pays',
            'zip-from'               => 'Code postal de',
            'zip-to'                 => 'Code postal à',
            'detect-automatically'   => 'Détecter automatiquement',
            'notes'                  => 'Notes',
            'company'                => 'Société',
        ],
        'tabs' => [
            'account-mapping' => [
                'title'            => 'Mappage des comptes',
                'add-action-label' => 'Ajouter un mappage de compte',
                'table'            => [
                    'columns' => [
                        'source-account'      => 'Compte source',
                        'destination-account' => 'Compte de destination',
                    ],
                ],
            ],
            'tax-mapping' => [
                'title'            => 'Mappage des taxes',
                'add-action-label' => 'Ajouter un mappage de taxe',
                'table'            => [
                    'columns' => [
                        'tax-source'      => 'Taxe source',
                        'tax-destination' => 'Taxe de destination',
                    ],
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'                 => 'Nom',
            'company'              => 'Société',
            'country'              => 'Pays',
            'country-group'        => 'Groupe de pays',
            'created-by'           => 'Créé par',
            'zip-from'             => 'Code postal de',
            'zip-to'               => 'Code postal à',
            'status'               => 'Statut',
            'detect-automatically' => 'Détecter automatiquement',
        ],

        'actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Position fiscale supprimée',
                    'body'  => 'La position fiscale a été supprimée avec succès.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Position fiscale supprimée',
                    'body'  => 'La position fiscale a été supprimée avec succès.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'entries' => [
            'name'                 => 'Nom',
            'foreign-vat'          => 'TVA étrangère',
            'country'              => 'Pays',
            'country-group'        => 'Groupe de pays',
            'zip-from'             => 'Code postal de',
            'zip-to'               => 'Code postal à',
            'detect-automatically' => 'Détecter automatiquement',
            'notes'                => 'Notes',
        ],
    ],
];
