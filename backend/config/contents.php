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
    ]
];
