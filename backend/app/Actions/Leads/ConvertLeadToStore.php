<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Models\Lead;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ConvertLeadToStore
{
    public function handle(Lead $lead, User $actor): Store
    {
        return DB::transaction(function () use ($lead, $actor): Store {
            $store = Store::query()->create([
                'name' => $lead->title,
                'slug' => $lead->slug ?? Str::slug($lead->title),
                'area_id' => $lead->area_id,
                'status' => 'active',
                'store_status' => 'pending',
                'extras' => [
                    'converted_from_lead_id' => $lead->id,
                    'converted_by_user_id' => $actor->id,
                ],
            ]);

            $lead->update([
                'status' => 'converted',
                'lead_status' => 'converted',
                'lead_fdd_status' => 'converted',
                'pipeline_phase' => 10,
            ]);

            return $store->fresh(['area']);
        });
    }
}
