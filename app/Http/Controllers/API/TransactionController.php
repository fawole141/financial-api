<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::where('user_id', auth()->id())
            ->orWhere('receiver_id', auth()->id())
            ->orderByDesc('created_at');

        if ($request->has('type')) {
            $query->where('type', $request->input('type')); // optional filter
        }

        $transactions = $query->get();

        return response()->json([
            'transactions' => $transactions
        ]);
    }
}
