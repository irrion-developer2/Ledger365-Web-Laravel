<?php

namespace App\Actions\Fortify;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Jetstream\Jetstream;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    // public function create(array $input): User
    // {
    //     Validator::make($input, [
    //         'name' => ['required', 'string', 'max:255'],
    //         'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
    //         'password' => $this->passwordRules(),
    //         'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
    //     ])->validate();

    //     return DB::transaction(function () use ($input) {
    //         $user = User::create([
    //             'name' => $input['name'],
    //             'email' => $input['email'],
    //             'phone' => $input['phone'],
    //             'role' => $input['role'],
    //             // 'status' => $input['status'],
    //             'tally_connector_id' => $input['tally_connector_id'],
    //             'password' => Hash::make($input['password']),
    //             'remember_token' => Str::random(60),
    //         ]);

    //         // Uncomment the following line if you want to create a team for the user
    //         // $this->createTeam($user);

    //         return $user;
            
    //     });
    // }

    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'numeric', 'unique:users'], // Ensure phone is unique
            'password' => $this->passwordRules(),
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
                    'password' => Hash::make($input['password']),
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
    
    /**
     * Create a personal team for the user.
     */
    // protected function createTeam(User $user): void
    // {
    //     $user->ownedTeams()->save(Team::forceCreate([
    //         'user_id' => $user->id,
    //         'name' => explode(' ', $user->name, 2)[0]."'s Team",
    //         'personal_team' => true,
    //     ]));
    // }
}
