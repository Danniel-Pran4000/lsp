<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'Membuat akun admin untuk verifikasi pendaftar';

    public function handle(): int
    {
        $validator = Validator::make([
            'name' => $this->ask('Nama admin'),
            'email' => $this->ask('Email admin'),
            'password' => $this->secret('Password admin'),
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'regex:/\A[^\x00]{1,72}\z/'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $admin = new User($validator->validated());
        $admin->role = 'admin';
        $admin->approval_status = null;
        $admin->save();

        $this->info('Akun admin berhasil dibuat.');

        return self::SUCCESS;
    }
}
