<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\ExchangeRate;


class ExchangeRateController extends Controller
{
    public function getRate(Request $request)
    {
        $validated = $request->validate([
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3'
        ]);

        $from = strtoupper($validated['from']);
        $to = strtoupper($validated['to']);
        $apiKey = config('services.fixer.key');

        // Free Fixer tier only allows base EUR
        $response = Http::get(config('services.fixer.base_url') . 'latest', [
            'access_key' => $apiKey,
            'symbols' => "$from,$to"
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to fetch exchange rates'], 500);
        }

        $data = $response->json();

        if (!isset($data['rates'][$from]) || !isset($data['rates'][$to])) {
            return response()->json([
                'error' => 'Unexpected response from Fixer.io',
                'raw' => $data
            ], 500);
        }

        // Convert rate: EUR → FROM, EUR → TO → calculate FROM → TO
        $rate = $data['rates'][$to] / $data['rates'][$from];

        return response()->json([
            'from' => $from,
            'to' => $to,
            'rate' => round($rate, 4),
            'date' => $data['date'] ?? now()->toDateString()
        ]);
    }
}
