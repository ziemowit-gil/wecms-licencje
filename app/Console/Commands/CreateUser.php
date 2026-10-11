<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/** Panel nie ma rejestracji — konta zakłada się z konsoli. */
class CreateUser extends Command
{
    protected $signature = 'licencje:user {email} {--name=}';

    protected $description = 'Załóż konto w panelu licencji albo zmień hasło istniejącego';

    public function handle(): int
    {
        $email = strtolower((string) $this->argument('email'));
        $password = (string) $this->secret('Hasło (min. 12 znaków)');

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => ['required', 'email'], 'password' => ['required', Password::min(12)]],
        );
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::updateOrCreate(['email' => $email], [
            'name' => $this->option('name') ?: (User::where('email', $email)->value('name') ?? $email),
            'password' => $password,
        ]);

        $this->info(($user->wasRecentlyCreated ? 'Założono konto ' : 'Zmieniono hasło dla ').$email);

        return self::SUCCESS;
    }
}
