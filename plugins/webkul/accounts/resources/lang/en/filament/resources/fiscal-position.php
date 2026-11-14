<?php

return [
    'model-label'        => 'Fiscal Position',
    'plural-model-label' => 'Fiscal Positions',

    'global-search' => [
        'zip-from' => 'Zip From',
        'zip-to'   => 'Zip To',
        'name'     => 'Name',
    ],

    'form' => [
        'fields' => [
            'name'                   => 'Name',
            'foreign-vat'            => 'Foreign VAT',
            'country'                => 'Country',
            'country-group'          => 'Country Group',
            'zip-from'               => 'Zip From',
            'zip-to'                 => 'Zip To',
            'detect-automatically'   => 'Detect Automatically',
            'notes'                  => 'Notes',
            'company'                => 'Company',
        ],
        'tabs' => [
            'account-mapping' => [
                'title'            => 'Account Mapping',
                'add-action-label' => 'Add Account Mapping',
                'table'            => [
                    'columns' => [
                        'source-account'      => 'Source Account',
                        'destination-account' => 'Destination Account',
                    ],
                ],
            ],
            'tax-mapping' => [
                'title'            => 'Tax Mapping',
                'add-action-label' => 'Add Tax Mapping',
                'table'            => [
                    'columns' => [
                        'tax-source'      => 'Tax Source',
                        'tax-destination' => 'Tax Destination',
                    ],
                ],
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'name'                 => 'Name',
            'company'              => 'Company',
            'country'              => 'Country',
            'country-group'        => 'Country Group',
            'created-by'           => 'Created By',
            'zip-from'             => 'Zip From',
            'zip-to'               => 'Zip To',
            'status'               => 'Status',
            'detect-automatically' => 'Detect Automatically',
        ],

        'actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Fiscal Position deleted',
                    'body'  => 'The fiscal position has been deleted successfully.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Fiscal Position deleted',
                    'body'  => 'The fiscal Position has been deleted successfully.',
                ],
            ],
        ],
    ],

    'infolist' => [
        'entries' => [
            'name'                 => 'Name',
            'foreign-vat'          => 'Foreign VAT',
            'country'              => 'Country',
            'country-group'        => 'Country Group',
            'zip-from'             => 'Zip From',
            'zip-to'               => 'Zip To',
            'detect-automatically' => 'Detect Automatically',
            'notes'                => 'Notes',
        ],
    ],
];
