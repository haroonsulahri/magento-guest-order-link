<?php

declare(strict_types=1);

return [
    'db-host' => '127.0.0.1',
    'db-user' => 'root',
    'db-password' => 'integration-test-only',
    'db-name' => 'magento_integration_tests',
    'db-prefix' => '',
    'backend-frontname' => 'backend',
    'search-engine' => 'opensearch',
    'opensearch-host' => '127.0.0.1',
    'opensearch-port' => 9200,
    'opensearch-index-prefix' => 'link_guest_order_to_customer_integration',
    'admin-user' => 'integration-admin',
    'admin-password' => 'Integration123!',
    'admin-email' => 'admin@example.com',
    'admin-firstname' => 'Example',
    'admin-lastname' => 'Administrator',
    'consumers-wait-for-messages' => '0',
];
