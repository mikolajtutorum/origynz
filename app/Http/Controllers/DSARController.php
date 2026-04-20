<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class DSARController extends Controller
{
    public function index(): View
    {
        return view('settings.data-export');
    }

    public function export(Request $request): Response
    {
        $user = $request->user();

        $data = [
            'exported_at' => now()->toIso8601String(),
            'profile' => [
                'name' => $user->name,
                'first_name' => $user->first_name,
                'middle_name' => $user->middle_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'birth_date' => $user->birth_date?->toDateString(),
                'country_of_residence' => $user->country_of_residence,
                'preferred_locale' => $user->preferred_locale,
                'ccpa_do_not_sell' => $user->ccpa_do_not_sell,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'family_trees' => $user->familyTrees()
                ->with(['people', 'mediaItems', 'sources'])
                ->get()
                ->map(fn ($tree) => [
                    'name' => $tree->name,
                    'privacy' => $tree->privacy,
                    'global_tree_enabled' => $tree->global_tree_enabled,
                    'created_at' => $tree->created_at?->toIso8601String(),
                    'people_count' => $tree->people->count(),
                    'people' => $tree->people->map(fn ($p) => [
                        'given_name' => $p->given_name,
                        'middle_name' => $p->middle_name,
                        'surname' => $p->surname,
                        'birth_date' => $p->birth_date,
                        'birth_place' => $p->birth_place,
                        'death_date' => $p->death_date,
                        'death_place' => $p->death_place,
                        'sex' => $p->sex,
                        'is_living' => $p->is_living,
                        'notes' => $p->notes,
                    ]),
                    'media_items' => $tree->mediaItems->map(fn ($m) => [
                        'file_name' => $m->file_name,
                        'description' => $m->description,
                        'created_at' => $m->created_at?->toIso8601String(),
                    ]),
                ]),
            'social_accounts' => $user->socialAccounts
                ->map(fn ($sa) => ['provider' => $sa->provider]),
            'activity_log' => Activity::where('causer_id', $user->id)
                ->where('causer_type', get_class($user))
                ->orderBy('created_at', 'desc')
                ->limit(1000)
                ->get()
                ->map(fn ($a) => [
                    'event' => $a->event,
                    'description' => $a->description,
                    'created_at' => $a->created_at?->toIso8601String(),
                ]),
        ];

        $filename = 'origynz-data-export-'.now()->format('Y-m-d').'.json';

        return response(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
