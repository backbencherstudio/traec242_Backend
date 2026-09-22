<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderManagementCustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'customer_info' => [
                'id' => $this->id,
                'image_url' => $this->image ? asset($this->image) : null,
                'name' => trim(($this->name ?? '').' '.($this->last_name ?? '')),
                'email' => $this->email,
                'total_order' => (int) ($this->total_order ?? 0),
                'complete_order' => (int) ($this->complete_order ?? 0),
                'pending_order' => (int) ($this->pending_order ?? 0),
                'total_spent' => '$'.number_format((float) ($this->total_spent ?? 0), 2),
            ],
        ];
    }
}
