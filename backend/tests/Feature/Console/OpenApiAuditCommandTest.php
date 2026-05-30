<?php

test('openapi audit passes when spec matches routes', function () {
    $this->artisan('openapi:audit', [
        '--spec' => base_path('../docs/api.openapi.yaml'),
        '--fail-on-drift' => true,
    ])->assertSuccessful();
});
