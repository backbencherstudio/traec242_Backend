<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);

        $query = User::where('type', 2);

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($qBuilder) use ($search) {
                $qBuilder->where('name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $category = $request->get('category');

            $query->where(function ($qb) use ($category) {
                $qb->whereJsonContains('category_id', $category)
                    ->orWhereExists(function ($sub) use ($category) {
                        $sub->select(\DB::raw(1))
                            ->from('services')
                            ->whereColumn('services.user_id', 'users.id')
                            ->where('services.category_id', $category);
                    });
            });
        }

        $providers = $query->paginate($perPage);

        return $this->sendResponse($providers);
    }

    public function show($id)
    {
        $provider = User::where('type', 2)->find($id);

        if (! $provider) {
            return $this->sendError('Provider not found', [], 404);
        }

        $services = Service::where('user_id', $provider->id)->get();

        return $this->sendResponse([
            'provider' => $provider,
            'services' => $services,
        ]);
    }
}
