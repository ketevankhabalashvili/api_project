<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BalanceHistory extends Model
{
    use HasFactory;

    protected $table = 'balance_histories';

    protected $fillable = [
        'recipient_user_ID', 'transaction_time', 'transaction_amount'
    ];

    public function user()
    {
        return $this->belongsTo(User::class,'recipient_user_ID');
    }
}
