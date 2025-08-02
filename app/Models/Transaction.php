<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
      protected $table = 'transactions';
   protected $fillable = [
    'user_id',
    'receiver_id',
    'type',
    'amount',
    'description',
];

}
