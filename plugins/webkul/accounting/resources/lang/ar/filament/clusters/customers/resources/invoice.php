<?php

return [
    'title' => 'فاتورة',

    'navigation' => [
        'title' => 'فواتير العملاء',
    ],

    'global-search' => [
        'customer' => 'العميل',
        'date'     => 'التاريخ',
        'due-date' => 'تاريخ الاستحقاق',
        'amount'   => 'المبلغ',
    ],

    'form' => [
        'tabs' => [
            'invoice-lines' => [
                'repeater' => [
                    'products' => [
                        'actions' => [
                            'open-product' => [
                                'tooltip' => 'فتح المنتج',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
