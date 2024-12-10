<?php

namespace App\Actions\Fortify;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Jetstream\Jetstream;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'numeric', 'unique:users'], // Ensure phone is unique
            // 'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        try {
            return DB::transaction(function () use ($input) {
                $user = User::create([
                    'name' => $input['name'],
                    'email' => $input['email'],
                    'phone' => $input['phone'],
                    'role' => $input['role'],
                    'tally_connector_id' => $input['tally_connector_id'],
                    // 'password' => Hash::make($input['password']),
                    'remember_token' => Str::random(60),
                ]);

                return $user;
            });
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') { // Check for unique constraint violation
                throw ValidationException::withMessages([
                    'phone' => 'The phone number has already been taken.',
                ]);
            }

            throw $e; // Re-throw exception if it's not a unique constraint violation
        }
    }
    
}
