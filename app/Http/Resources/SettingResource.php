<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'site_name' => $this->site_name,
            'site_logo' => $this->site_logo ? asset($this->site_logo) : null,
            'admin_logo' => $this->admin_logo ? asset($this->admin_logo) : null,
            'favicon' => $this->favicon ? asset($this->favicon) : null,
            'seo_image' => $this->seo_image ? asset($this->seo_image) : null,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'copyright' => $this->copyright,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
