<?php

namespace App\Console\Commands;

use App\Jobs\SyncSimpleFinAccounts as SyncSimpleFinAccountsJob;
use App\Models\SimpleFinToken;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

class SyncSimpleFinAccounts extends Command
{
    protected $signature = 'app:simplefin:sync {userId?}';

    protected $description = 'Sync accounts and transactions via SimpleFIN Bridge';

    public function handle(): int
    {
        $userId = $this->argument('userId');

        $tokens = SimpleFinToken::when($userId, function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->get();

        $tokens->each(function ($token) {
            SyncSimpleFinAccountsJob::dispatch($token);
        });

        return Command::SUCCESS;
    }
}
