<?php

return [
    // Server-provisioned households. Never read endpoints or entity allowlists from a customer request.
    'gateways' => json_decode((string) env('SPAD_SMART_HOME_GATEWAYS', '{}'), true) ?: [],
];
