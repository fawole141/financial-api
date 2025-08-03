<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Database\Eloquent\Factories\HasFactory;


use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_fund_wallet()
    {
        // Create user
        $user = User::factory()->create();

        // Create wallet for user
        Wallet::create([
            'user_id' => $user->id,
            'balance' => 0,
            'currency' => 'USD'
        ]);

        // Authenticate and get token
        $token = auth()->login($user);

        // Send funding request
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/wallet/fund', [
                             'amount' => 100,
                             'currency' => 'USD',
                         ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Wallet funded successfully',
                 ]);

        // Assert balance was updated
        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 100.00
        ]);
    }

    public function test_user_can_transfer_to_another_user()
{
    // Create sender and receiver
    $sender = \App\Models\User::factory()->create();
    $receiver = \App\Models\User::factory()->create();

    // Create wallets
    \App\Models\Wallet::create([
        'user_id' => $sender->id,
        'balance' => 200,
        'currency' => 'USD'
    ]);

    \App\Models\Wallet::create([
        'user_id' => $receiver->id,
        'balance' => 50,
        'currency' => 'USD'
    ]);

    // Log in sender
    $token = auth()->login($sender);

    // Send transfer request
    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/wallet/transfer', [
    'to_user_id' => $receiver->id,
    'amount' => 100,
]);


  

    // Check response
    $response->assertStatus(200)
             ->assertJson([
                 'message' => 'Transfer completed',
             ]);

    // Assert sender's balance is reduced
    $this->assertDatabaseHas('wallets', [
        'user_id' => $sender->id,
        'balance' => 100.00
    ]);

    // Assert receiver's balance is increased
    $this->assertDatabaseHas('wallets', [
        'user_id' => $receiver->id,
        'balance' => 150.00
    ]);

    // Assert transaction is recorded
    $this->assertDatabaseHas('transactions', [
        'user_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'amount' => 100.00,
        'type' => 'transfer'
    ]);
}

}
