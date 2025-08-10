<?php

namespace Tests\Unit\Console\Commands;

use App\Jobs\SyncSimpleFinAccounts;
use App\Models\SimpleFinToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SyncSimpleFinAccountsTest extends TestCase
{
    use RefreshDatabase;

    private const COMMAND = 'app:simplefin:sync';

    /** @test */
    public function dispatches_job_for_each_token(): void
    {
        $token = SimpleFinToken::factory()->create();

        Queue::fake();

        $this->artisan(self::COMMAND)->assertExitCode(0);

        Queue::assertPushed(SyncSimpleFinAccounts::class, function ($job) use ($token) {
            return $job->token->id === $token->id;
        });
    }
}

