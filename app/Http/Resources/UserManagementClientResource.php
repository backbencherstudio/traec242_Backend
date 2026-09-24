<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserManagementClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'image_url' => $this->image ? asset($this->image) : null,
            'name' => trim(($this->name ?? '').' '.($this->last_name ?? '')),
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => trim(
                ($this->address ?? '').', '.
                ($this->city ?? '').', '.
                ($this->state ?? '').' '.
                ($this->zip_code ?? '')
            ),
            'joined' => $this->created_at?->format('m/d/Y'),
            'total_orders' => (int) ($this->total_orders ?? 0),
            'total_spent' => '$'.number_format((float) ($this->total_spent ?? 0), 2),
            'status' => $this->status ? 'Active' : 'Inactive',
            'is_verified' => (bool) $this->is_verified,
        ];
    }
}
