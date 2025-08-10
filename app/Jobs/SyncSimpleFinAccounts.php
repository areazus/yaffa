<?php

namespace App\Jobs;

use App\Models\SimpleFinToken;
use App\Services\SimpleFinService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncSimpleFinAccounts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public SimpleFinToken $token;

    public function __construct(SimpleFinToken $token)
    {
        $this->token = $token;
    }

    public function handle(): void
    {
        $service = new SimpleFinService();
        $service->sync($this->token);
    }
}
