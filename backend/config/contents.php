<?php

return [
    'homepage_reviews' => [
        'fields' => [
            'rating' => [
                'type' => 'number',
                'label' => 'Rating',
                'min' => 1,
                'max' => 5,
            ],
            'name' => [
                'type' => 'string',
                'label' => 'Reviewer Name',
            ],
            'logo' => [
                'type' => 'file',
                'label' => 'Logo URL',
                'accept' => 'image/*',
            ],
            'photo' => [
                'type' => 'file',
                'label' => 'Photo URL',
                'accept' => 'image/*',
            ],
            'text' => [
                'type' => 'string',
                'label' => 'Review Text',
                'multiline' => true,
            ],
        ]
    ],
    'saving_on_subscriptions' => [
        'fields' => [
            'logo' => [
                'type' => 'file',
                'label' => 'Service logo URL',
                'accept' => 'image/*',
            ],
            'text' => [
                'type' => 'string',
                'label' => 'Marketing Text',
                'multiline' => true,
            ],
            'advantage' => [
                'type' => 'string',
                'label' => 'Advantage',
                'multiline' => true,
            ],
            'normal_price' => [
                'type' => 'string',
                'label' => 'Normal price',
            ],
            'our_price' => [
                'type' => 'string',
                'label' => 'Our price',
            ],
            'serviceId' => [
                'type' => 'service',
                'label' => 'Service',
            ],
        ]
    ]
];
