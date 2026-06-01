<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\AiAssistant;
use App\Domain\AppConfig;
use App\Domain\Contact;
use App\Domain\Document;
use App\Domain\Settings;
use App\Models\AchCustomer;
use App\Models\AchTransfer;
use App\Models\AiThread;
use App\Models\Closing;
use App\Models\Communication;
use App\Models\Document as DocumentModel;
use App\Models\Fdd;
use App\Models\FieldGroup;
use App\Models\InterestRegion;
use App\Models\Lead;
use App\Models\PosConnection;
use App\Models\RoyaltyPeriod;
use App\Models\Store;
use App\Models\User;
use App\Policies\AchPolicy;
use App\Policies\AiPolicy;
use App\Policies\AiThreadPolicy;
use App\Policies\AppConfigPolicy;
use App\Policies\ClosingPolicy;
use App\Policies\CommunicationPolicy;
use App\Policies\ContactPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\FddPolicy;
use App\Policies\FieldSchemaPolicy;
use App\Policies\InterestRegionPolicy;
use App\Policies\LeadPolicy;
use App\Policies\PosPolicy;
use App\Policies\RoyaltyPolicy;
use App\Policies\SettingPolicy;
use App\Policies\StaffPolicy;
use App\Policies\StorePolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

final class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Lead::class => LeadPolicy::class,
        Store::class => StorePolicy::class,
        Fdd::class => FddPolicy::class,
        Closing::class => ClosingPolicy::class,
        Communication::class => CommunicationPolicy::class,
        FieldGroup::class => FieldSchemaPolicy::class,
        InterestRegion::class => InterestRegionPolicy::class,
        Contact::class => ContactPolicy::class,
        Document::class => DocumentPolicy::class,
        DocumentModel::class => DocumentPolicy::class,
        AppConfig::class => AppConfigPolicy::class,
        AiAssistant::class => AiPolicy::class,
        Settings::class => SettingPolicy::class,
        RoyaltyPeriod::class => RoyaltyPolicy::class,
        AchTransfer::class => AchPolicy::class,
        AchCustomer::class => AchPolicy::class,
        PosConnection::class => PosPolicy::class,
        User::class => UserPolicy::class,
        AiThread::class => AiThreadPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('accessStaffApp', [StaffPolicy::class, 'accessStaffApp']);
        Gate::define('viewContact', fn (User $user, User $contact): bool => app(ContactPolicy::class)->view($user, $contact));
        Gate::define('updateContact', fn (User $user, User $contact): bool => app(ContactPolicy::class)->update($user, $contact));
        Gate::define('viewAnyRoyalty', [RoyaltyPolicy::class, 'viewAny']);
        Gate::define('manageRoyalty', [RoyaltyPolicy::class, 'manage']);
        Gate::define('viewAnyAch', [AchPolicy::class, 'viewAny']);
        Gate::define('manageAch', [AchPolicy::class, 'manage']);
        Gate::define('viewFieldSchemaForEntity', [FieldSchemaPolicy::class, 'viewForEntity']);
        Gate::define('manageFields', [FieldSchemaPolicy::class, 'manage']);
    }
}
