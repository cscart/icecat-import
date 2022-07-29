<?php
declare(strict_types=1);


return [
    'base_url' => 'http://data.icecat.biz',
    'entities' => [
        'products'   => [
            'local_path' => storage_path('products.index.xml.gz'),
            'url'        => 'https://data.icecat.biz/export/freexml/EN/files.index.xml.gz'
        ],
        'categories' => [
            'local_path' => storage_path('categories.index.xml.gz'),
            'url'        => 'https://data.icecat.biz/export/freexml/refs/CategoriesList.xml.gz'
        ],
        'features'   => [
            'local_path' => storage_path('features.index.xml.gz'),
            'url'        => 'https://data.icecat.biz/export/freexml.int/refs/FeaturesList.xml.gz'
        ]
    ],
    'jobs'     => [
        'entity_count' => 50
    ]
];
