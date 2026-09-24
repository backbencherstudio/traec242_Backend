<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDashboardRecentOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'event_name' => $this->event_name,
            'order_by' => trim(
                ($this->service?->user?->name ?? '').' '.
                ($this->service?->user?->last_name ?? '')
            ),
            'date' => $this->event_start_date ? Carbon::parse($this->event_start_date)->format('M d, Y') : null,
            'status' => ucfirst((string) $this->status),
        ];
    }
}
