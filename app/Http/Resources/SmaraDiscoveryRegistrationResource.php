<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SmaraDiscoveryRegistration */
class SmaraDiscoveryRegistrationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'city' => $this->city,
            'phone' => $this->phone,
            'email' => $this->email,
            'age_group' => $this->age_group,
            'has_visited_es_smara' => $this->has_visited_es_smara,
            'interest_level' => $this->interest_level,
            'participants_count' => $this->participants_count,
            'preferred_duration' => $this->preferred_duration,
            'departure_city' => $this->departure_city,
            'budget' => $this->budget,
            'preferred_activities' => $this->preferred_activities,
            'notify_first_date' => $this->notify_first_date,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
