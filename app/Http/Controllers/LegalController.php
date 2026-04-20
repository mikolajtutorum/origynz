<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LegalController extends Controller
{
    public function privacy(): View
    {
        return view('legal.privacy-policy');
    }

    public function terms(): View
    {
        return view('legal.terms-of-service');
    }

    public function dpa(): View
    {
        return view('legal.dpa');
    }

    public function ccpa(Request $request): View
    {
        return view('legal.ccpa', [
            'optedOut' => $request->user()?->ccpa_do_not_sell ?? false,
        ]);
    }

    public function ccpaOptOut(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->ccpa_do_not_sell = ! $user->ccpa_do_not_sell;
        $user->save();

        $message = $user->ccpa_do_not_sell
            ? __('Your opt-out preference has been saved.')
            : __('Your opt-out has been removed.');

        return redirect()->route('legal.ccpa')->with('status', $message);
    }
}
