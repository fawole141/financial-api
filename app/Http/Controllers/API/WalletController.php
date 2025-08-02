<?php
namespace App\Http\Controllers\API;

use App\Models\User;

use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{

    public function fund(Request $request)
    {
    $validated = $request->validate([
        'amount' => 'required|numeric|min:1|max:1000000'
    ]);

    try {
        $user = auth('api')->user();
        $wallet = $user->wallet;

        if (!$wallet) {
            return response()->json(['error' => 'Wallet not found'], 404);
        }

        DB::transaction(function () use ($wallet, $validated, $user) {
            $amount = $validated['amount'];

            $wallet->balance += $amount;
            $wallet->save();

            $user->transactions()->create([
                'type' => 'fund',
                'amount' => $amount,
                'description' => 'Wallet funded'
            ]);
        });

        //  Return response after transaction is complete
        return response()->json([
            'message' => 'Wallet funded successfully',
            'balance' => number_format($wallet->fresh()->balance, 2)
        ], 200);

    } catch (\Exception $e) {
        \Log::error('Fund wallet error', ['error' => $e->getMessage()]);
        return response()->json([
            'error' => 'Something went wrong while funding wallet'
        ], 500);
    }
}


public function transfer(Request $request)
{
    $validated = $request->validate([
        'to_user_id' => 'required|exists:users,id',
        'amount' => 'required|numeric|min:0.01',
    ]);

    $senderWallet = Wallet::where('user_id', auth()->id())->firstOrFail();
    $receiverWallet = Wallet::where('user_id', $validated['to_user_id'])->firstOrFail();

    $fromCurrency = strtoupper($senderWallet->currency);
    $toCurrency = strtoupper($receiverWallet->currency);
    $amount = $validated['amount'];

    if ($senderWallet->balance < $amount) {
        return response()->json(['error' => 'Insufficient balance'], 400);
    }

    $convertedAmount = $amount;
    $rateUsed = 1;

    // If currencies differ, use Fixer to convert
    if ($fromCurrency !== $toCurrency) {
        $response = Http::get(config('services.fixer.base_url') . 'latest', [
            'access_key' => config('services.fixer.key'),
            'symbols' => "$fromCurrency,$toCurrency"
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Exchange rate API error'], 500);
        }

        $data = $response->json();

        if (!isset($data['rates'][$fromCurrency]) || !isset($data['rates'][$toCurrency])) {
            return response()->json(['error' => 'Currency not supported'], 400);
        }

        $rateUsed = $data['rates'][$toCurrency] / $data['rates'][$fromCurrency];
        $convertedAmount = round($amount * $rateUsed, 2);

        // Log exchange rate
        ExchangeRate::create([
            'from' => $fromCurrency,
            'to' => $toCurrency,
            'rate' => $rateUsed,
            'date' => $data['date'] ?? now()->toDateString(),
        ]);
    }

    // Perform wallet updates
    $senderWallet->decrement('balance', $amount);
    $receiverWallet->increment('balance', $convertedAmount);

    // Log transaction
Transaction::create([
    'user_id' => auth()->id(), // sender
    'receiver_id' => $validated['to_user_id'],
    'type' => 'transfer',
    'amount' => $convertedAmount,
    'description' => "Transfer to User #{$validated['to_user_id']}",
]);




    return response()->json([
        'message' => 'Transfer completed',
        'from_currency' => $fromCurrency,
        'to_currency' => $toCurrency,
        'amount_sent' => $amount,
        'amount_received' => $convertedAmount,
        'exchange_rate' => round($rateUsed, 4),
    ]);
}


}
