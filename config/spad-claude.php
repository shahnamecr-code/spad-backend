<?php

// Claude API for the renovation studio's room analysis (image understanding only).
return [
    'key' => env('SPAD_CLAUDE_API_KEY', ''),
    'model' => env('SPAD_CLAUDE_MODEL', 'claude-opus-5-5'),
    'endpoint' => env('SPAD_CLAUDE_ENDPOINT', 'https://api.anthropic.com/v1/messages'),
];
