<?php

declare(strict_types=1);
use App\Providers\AuthServiceProvider;
use Illuminate\Support\Facades\Gate;

test('all registered policy classes exist and resolve', function () {
    $provider = new ReflectionClass(AuthServiceProvider::class);
    $property = $provider->getProperty('policies');
    $property->setAccessible(true);

    /** @var array<class-string, class-string> $policies */
    $policies = $property->getValue(new AuthServiceProvider($this->app));

    foreach ($policies as $modelClass => $policyClass) {
        expect(class_exists($policyClass))->toBeTrue("Policy class [{$policyClass}] for model [{$modelClass}] does not exist.");

        expect(Gate::getPolicyFor($modelClass)::class)->toBe($policyClass, "Gate could not resolve policy for model [{$modelClass}].");
    }
});
