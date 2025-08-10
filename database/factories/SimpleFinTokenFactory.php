<?php

namespace Database\Factories;

use App\Models\SimpleFinToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SimpleFinTokenFactory extends Factory
{
    protected $model = SimpleFinToken::class;

    public function definition(): array
    {
        return [
            'access_url' => 'https://demo:pass@beta-bridge.simplefin.org/simplefin/claim/DEMO',
            'user_id' => User::factory(),
        ];
    }
}

