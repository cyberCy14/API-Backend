<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialReport extends Model
{
    use HasFactory;

    protected $table = 'financial_report';

    protected $fillable = [
        'user_id',
        'transaction_title',
        'type',
        'amount',
    ];

   
    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public static function getTotalIncome($userId = null)
    {
        return FinancialReport::when($userId, fn($q) => $q->where('user_id', $userId))
            ->where('type', 'income')
            ->sum('amount');
    }

    public static function getTotalExpense($userId = null)
    {
        return FinancialReport::when($userId, fn($q) => $q->where('user_id', $userId))
            ->where('type', 'expense')
            ->sum('amount');
    }

    public static function getTotalAssets($userId = null)
    {
        return FinancialReport::when($userId, fn($q) => $q->where('user_id', $userId))
            ->where('type', 'asset')
            ->sum('amount');
    }

    public static function getTotalLiabilities($userId = null)
    {
        return FinancialReport::when($userId, fn($q) => $q->where('user_id', $userId))
            ->where('type', 'liability')
            ->sum('amount');
    }

    public static function getNetProfit($userId = null)
    {
        return FinancialReport::getTotalIncome($userId) - FinancialReport::getTotalExpense($userId);
    }
}