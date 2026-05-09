<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminResetPassword extends Command
{
    protected $signature = 'admin:reset-password
                            {email : Email администратора}
                            {--password= : Новый пароль (если не указан - генерируется автоматически)}';

    protected $description = 'Сброс пароля администратора через CLI (используется если SMTP недоступен)';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Пользователь с email \"{$email}\" не найден.");

            return 1;
        }

        $password = $this->option('password') ?? Str::random(16);

        $user->update(['password' => Hash::make($password)]);

        $this->info("Пароль для {$email} успешно изменён.");

        if (!$this->option('password')) {
            $this->line('');
            $this->line("  Сгенерированный пароль: <fg=yellow;options=bold>{$password}</>");
            $this->line('');
            $this->warn('  Сохраните этот пароль - он больше не будет показан.');
        }

        return 0;
    }
}
