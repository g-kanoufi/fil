<?php

return [

    'paths' => ['api/public/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => config('fil.embed_allowed_origins', []),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
