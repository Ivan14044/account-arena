<?php

return array(
    'new_payment_title' => 'New payment(:method)',
    'new_payment_message' => 'New payment(:method), email: :email, name: :name',
    'new_subscription_title' => 'New subscription(:method)',
    'new_subscription_message' => 'New subscription(:method), email: :email, name: :name',

    'new_user_title' => 'New user',
    'new_user_message' => 'New user registered, email: :email, name: :name',

    'new_product_purchase_title' => 'New purchase (:method)',
    'new_product_purchase_message' => 'New purchase (:method), email: :email, name: :name, products: :products, amount: :amount',

    'types' => [
        'product_purchase' => 'Product Purchase',
        'balance_topup' => 'Balance Top-up',
        'manual_delivery' => 'Manual Delivery',
        'manual_delivery_new_order' => 'New Order (Manual)',
        'dispute_created' => 'Product Dispute',
        'support_chat' => 'Support Chat',
        'new_user' => 'New User',
        'system' => 'System',
    ],
);
