<?php

namespace App\Actions\Fortify;

use App\Actions\CreateDefaultFamilyTree;
use App\Concerns\PasswordValidationRules;
use App\Models\User;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => $this->passwordRules(),
            'terms' => ['required', 'accepted'],
            'age_confirmation' => ['required', 'accepted'],
        ], [
            'terms.required' => __('You must agree to the Terms of Service and Privacy Policy.'),
            'terms.accepted' => __('You must agree to the Terms of Service and Privacy Policy.'),
            'age_confirmation.required' => __('You must confirm you are 13 years of age or older.'),
            'age_confirmation.accepted' => __('You must confirm you are 13 years of age or older.'),
        ])->validate();

        [$firstName, $middleName, $lastName] = $this->splitName($input['name']);

        $user = User::create([
            'name' => $input['name'],
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'preferred_locale' => app()->getLocale(),
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        app(TreeAccessService::class)->assignDefaultRole($user);
        app(TreeAccessService::class)->syncPendingAccessForUser($user);
        app(CreateDefaultFamilyTree::class)->execute($user);

        return $user;
    }

    /**
     * @return array{0:string,1:?string,2:string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $parts = array_values(array_filter($parts, fn (string $part) => $part !== ''));

        if ($parts === []) {
            return ['Account', null, 'Owner'];
        }

        if (count($parts) === 1) {
            return [$parts[0], null, ''];
        }

        $firstName = array_shift($parts);
        $lastName = array_pop($parts);
        $middleName = $parts !== [] ? Str::of(implode(' ', $parts))->trim()->toString() : null;

        return [$firstName ?: 'Account', $middleName, $lastName ?: 'Owner'];
    }
}
