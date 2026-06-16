<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class DocumentationController extends Controller
{
    public function __invoke(): View
    {
        return view('api.docs', [
            'baseUrl'    => url('/api/v1'),
            'rateLimit'  => config('integrations.api.rate_limit', 60),
        ]);
    }
}
