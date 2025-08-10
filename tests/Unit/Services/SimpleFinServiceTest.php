<?php

namespace Tests\Unit\Services;

use App\Models\AccountGroup;
use App\Models\Currency;
use App\Models\SimpleFinToken;
use App\Models\User;
use App\Services\SimpleFinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SimpleFinServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function sync_creates_accounts_and_transactions(): void
    {
        $user = User::factory()->create();
        Currency::factory()->for($user)->create(['base' => true]);
        AccountGroup::factory()->for($user)->create();

        $token = SimpleFinToken::factory()->for($user)->create();

        $data = [
            'accounts' => [
                [
                    'id' => 'demo',
                    'name' => 'Demo Account',
                    'balance' => 1000,
                    'balance-date' => time(),
                    'transactions' => [
                        [
                            'posted' => time(),
                            'amount' => -50,
                            'description' => 'Coffee',
                        ],
                    ],
                ],
            ],
        ];

        Http::fake([ '*' => Http::response($data, 200) ]);

        $service = new SimpleFinService();
        $service->sync($token);

        $this->assertDatabaseHas('account_entities', [
            'name' => 'Demo Account',
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('transactions', [
            'comment' => 'Coffee',
            'user_id' => $user->id,
        ]);
    }
}

