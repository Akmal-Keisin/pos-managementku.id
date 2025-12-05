<?php

return [
    // Enable or disable DB tool globally
    'db_tool_enabled' => env('CHATBOT_DB_TOOL_ENABLED', true),

    // Whitelisted tables and columns exposed via the DB tool
    'whitelist' => [
        'products' => [
            'id', 'name', 'sku', 'current_stock', 'price', 'description'
        ],
        'stock_histories' => [
            'id', 'product_id', 'type', 'quantity', 'notes', 'created_at'
        ],
        'transactions' => [
            'id', 'user_id', 'total', 'status', 'created_at'
        ],
        'transaction_details' => [
            'transaction_id', 'product_id', 'quantity', 'price'
        ],
        // users intentionally not exposed unless explicitly added here
    ],

    // Max rows returned by any DB tool call
    'row_limit' => env('CHATBOT_DB_TOOL_ROW_LIMIT', 50),
    // Allow full table export via the tool (disabled by default for safety)
    'allow_full_export' => env('CHATBOT_DB_TOOL_ALLOW_FULL_EXPORT', false),
];
