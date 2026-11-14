<?php

return [
    'navigation' => [
        'title' => 'Relatórios',
        'group' => 'Estoque',
    ],

    'moves' => [
        'model-label'        => 'Movimentação de estoque',
        'plural-model-label' => 'Movimentações de estoque',

        'navigation' => [
            'title' => 'Histórico de movimentações',
        ],

        'filters' => [
            'product-category'     => 'Categoria de produto',
            'source-location'      => 'Local de origem',
            'destination-location' => 'Local de destino',
            'package'              => 'Embalagem',
            'lot'                  => 'Número de lote/série',
            'package-type'         => 'Tipo de embalagem',
        ],

        'groups' => [
            'product'   => 'Produto',
            'status'    => 'Status',
            'date'      => 'Data',
            'operation' => 'Operação',
            'location'  => 'Localização',
            'category'  => 'Categoria',
        ],
    ],

    'quantities' => [
        'model-label'        => 'Quantidade de estoque',
        'plural-model-label' => 'Quantidades de estoque',

        'navigation' => [
            'title' => 'Locais',
        ],

        'filters' => [
            'warehouse'        => 'Armazém',
            'location'         => 'Localização',
            'product-category' => 'Categoria de produto',
            'storage-category' => 'Categoria de armazenamento',
            'package'          => 'Embalagem',
            'lot'              => 'Número de lote/série',
            'package-type'     => 'Tipo de embalagem',
        ],

        'groups' => [
            'product'          => 'Produto',
            'product-category' => 'Categoria de produto',
            'location'         => 'Localização',
            'storage-category' => 'Categoria de armazenamento',
            'lot'              => 'Número de lote/série',
            'package'          => 'Embalagem',
            'company'          => 'Empresa',
        ],
    ],
];
