<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountEntity;
use App\Models\SimpleFinToken;
use App\Models\Transaction;
use App\Models\TransactionDetailStandard;
use App\Models\TransactionType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class SimpleFinService
{
    public function sync(SimpleFinToken $token): void
    {
        $accessUrl = $token->access_url;
        $parts = parse_url($accessUrl);
        $username = $parts['user'] ?? null;
        $password = $parts['pass'] ?? null;
        $base = $parts['scheme'].'://'.$parts['host'].($parts['path'] ?? '');

        $response = Http::withBasicAuth($username, $password)->get($base.'/accounts');
        if ($response->failed()) {
            return;
        }

        $data = $response->json();
        $user = $token->user;
        $accountGroup = $user->accountGroups()->firstOrCreate(['name' => 'SimpleFIN']);
        $currency = $user->baseCurrency() ?? $user->currencies()->first();

        foreach ($data['accounts'] ?? [] as $remote) {
            $accountEntity = $user->accounts()->where('name', $remote['name'])->first();
            if (!$accountEntity) {
                $account = Account::create([
                    'opening_balance' => $remote['balance'] ?? 0,
                    'account_group_id' => $accountGroup->id,
                    'currency_id' => $currency->id,
                    'simplefin_account_id' => $remote['id'] ?? null,
                ]);

                $accountEntity = AccountEntity::create([
                    'name' => $remote['name'],
                    'active' => true,
                    'config_type' => 'account',
                    'config_id' => $account->id,
                    'user_id' => $user->id,
                ]);
            } else {
                $account = $accountEntity->config;
            }

            foreach ($remote['transactions'] ?? [] as $tx) {
                $date = Carbon::createFromTimestamp($tx['posted'])->format('Y-m-d');
                $amount = (float) $tx['amount'];
                $description = $tx['description'] ?? '';

                $exists = Transaction::where('user_id', $user->id)
                    ->where('date', $date)
                    ->where('comment', $description)
                    ->whereHas('config', function ($q) use ($accountEntity) {
                        $q->where('account_from_id', $accountEntity->id)
                          ->orWhere('account_to_id', $accountEntity->id);
                    })
                    ->exists();

                if ($exists) {
                    continue;
                }

                $typeName = $amount < 0 ? 'withdrawal' : 'deposit';
                $type = TransactionType::where('name', $typeName)->first();

                $detail = TransactionDetailStandard::create([
                    $amount < 0 ? 'account_from_id' : 'account_to_id' => $accountEntity->id,
                    'amount_from' => abs($amount),
                    'amount_to' => abs($amount),
                ]);

                Transaction::create([
                    'user_id' => $user->id,
                    'date' => $date,
                    'transaction_type_id' => $type->id,
                    'reconciled' => true,
                    'schedule' => false,
                    'budget' => false,
                    'comment' => $description,
                    'config_type' => 'standard',
                    'config_id' => $detail->id,
                ]);
            }
        }
    }
}
