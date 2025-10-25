<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomerPoint;

class CustomerPointsController extends Controller
{
   public function index(string $customer_id, \Illuminate\Http\Request $request)
{
    $companyId = $request->query('company_id');
    $limit     = (int) $request->query('limit', 5);

    $query = \App\Models\CustomerPoint::query()
        ->where('customer_id', $customer_id)
        ->where('status', 'completed');   

    if ($companyId) {
        $query->where('company_id', $companyId);
    }

    $points = $query
        ->orderBy('created_at', 'desc')
        ->take($limit)
        ->get();

    return response()->json([
        'transactions' => $points,       
    ]);
}

}