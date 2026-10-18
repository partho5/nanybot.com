<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateAdmin extends Command
{
    protected $signature = 'blog:create-admin {email} {--name=Admin} {--password=}';

    protected $description = 'Create (or promote) an admin user. Public registration is disabled.';

    public function handle(): int
    {
        $password = $this->option('password') ?: Str::password(20);

        $user = User::firstOrNew(['email' => $this->argument('email')]);

        // Username is part of the public author URL: set it once, on creation only.
        if (! $user->exists) {
            $user->username = Str::slug($this->option('name')).'-'.Str::lower(Str::random(4));
        }

        $user->forceFill([
            'name' => $this->option('name'),
            'role' => UserRole::Admin,
            'password' => $password,
            'email_verified_at' => now(),
        ])->save();

        $this->info("Admin ready: {$user->email}");
        if (! $this->option('password')) {
            $this->warn("Generated password: {$password}");
        }

        return self::SUCCESS;
    }
}
