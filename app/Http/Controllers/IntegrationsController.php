<?php

namespace App\Http\Controllers;

use App\Enums\IntegrationProvider;
use App\Models\UserIntegration;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class IntegrationsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $connected = UserIntegration::where('user_id', $user->id)
            ->get()
            ->keyBy(fn ($i) => $i->provider->value);

        return view('integrations.index', [
            'connected'    => $connected,
            'providers'    => IntegrationProvider::cases(),
            'fsFeatured'   => (bool) config('integrations.familysearch.client_id'),
            'geniFeatured' => (bool) config('integrations.geni.client_id'),
        ]);
    }
}
