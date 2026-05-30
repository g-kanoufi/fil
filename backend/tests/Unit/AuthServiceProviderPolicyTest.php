<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Providers\AuthServiceProvider;
use Illuminate\Support\Facades\Gate;
use ReflectionClass;
use Tests\TestCase;

final class AuthServiceProviderPolicyTest extends TestCase
{
    public function test_all_registered_policy_classes_exist_and_resolve(): void
    {
        $provider = new ReflectionClass(AuthServiceProvider::class);
        $property = $provider->getProperty('policies');
        $property->setAccessible(true);

        /** @var array<class-string, class-string> $policies */
        $policies = $property->getValue(new AuthServiceProvider($this->app));

        foreach ($policies as $modelClass => $policyClass) {
            $this->assertTrue(
                class_exists($policyClass),
                "Policy class [{$policyClass}] for model [{$modelClass}] does not exist.",
            );

            $this->assertSame(
                $policyClass,
                Gate::getPolicyFor($modelClass)::class,
                "Gate could not resolve policy for model [{$modelClass}].",
            );
        }
    }
}
