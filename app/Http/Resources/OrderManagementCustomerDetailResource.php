<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderManagementCustomerDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'customer' => [
                'id' => $this->id,
                'name' => trim(($this->name ?? '').' '.($this->last_name ?? '')),
                'email' => $this->email,
                'phone' => $this->phone,
                'image_url' => $this->image ? asset($this->image) : null,
                'address' => trim(
                    ($this->address ?? '').', '.
                    ($this->city ?? '').', '.
                    ($this->state ?? '').' '.
                    ($this->zip_code ?? '')
                ),
                'status' => $this->status ? 'Active' : 'Inactive',
                'is_verified' => (bool) $this->is_verified,
                'joined' => $this->created_at?->format('m/d/Y'),
            ],
            'orders' => [
                'total_orders' => (int) ($this->resource->total_orders ?? 0),
                'completed_orders' => (int) ($this->resource->completed_orders ?? 0),
                'pending_orders' => (int) ($this->resource->pending_orders ?? 0),
            ],
            'payments' => [
                'total_spent' => '$'.number_format((float) ($this->resource->total_spent ?? 0), 2),
            ],
        ];
    }
}
