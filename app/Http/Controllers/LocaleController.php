<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $locale = $request->input('locale');

        if (in_array($locale, array_keys(config('app.locales', [])), true)) {
            session(['locale' => $locale]);
            app()->setLocale($locale);
        }

        return back();
    }
}
