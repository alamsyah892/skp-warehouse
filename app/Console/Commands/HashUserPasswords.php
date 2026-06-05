<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class HashUserPasswords extends Command
{
    protected $signature = 'users:hash-passwords';

    protected $description = 'Hash plain user passwords and skip empty values.';

    public function handle(): int
    {
        $hashedCount = 0;
        $skippedCount = 0;

        DB::table('users')
            ->select(['id', 'password'])
            ->orderBy('id')
            ->lazyById()
            ->each(function (object $user) use (&$hashedCount, &$skippedCount): void {
                $password = (string) $user->password;

                if ($password === '') {
                    $skippedCount++;

                    return;
                }

                if (!Hash::needsRehash($password)) {
                    $skippedCount++;

                    return;
                }

                DB::table('users')
                    ->where('id', $user->id)
                    ->whereNotIn('id', [1, 2])
                    ->update([
                        'password' => Hash::make($password),
                        'updated_at' => now(),
                    ]);

                $hashedCount++;
            });

        $this->components->info("Hashed {$hashedCount} user password(s). Skipped {$skippedCount} user password(s).");

        return self::SUCCESS;
    }
}
