<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Bind Laravel's TestCase for Feature and Unit tests. RefreshDatabase and
| other traits are applied per-file via uses() after Drift conversion.
|
*/

pest()->extend(TestCase::class)->in('Feature', 'Unit');
