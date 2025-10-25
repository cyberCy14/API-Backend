<?php

namespace App\Http\Controllers;
use App\Models\CustomerCompanyBalance;
use App\Models\CustomerPoint;
use Illuminate\Http\Request;
use App\Http\Resources\CustomerCompanyBalanceResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CustomerCompanyBalancesController extends Controller
{

public function index(Request $request)
{
    $customerId = Auth::id();

    $rows = CustomerPoint::query()
        ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
        ->where('status', 'completed') 
        ->selectRaw('company_id, customer_id, SUM(points_earned) as total_balance')
        ->groupBy('company_id', 'customer_id')
        ->with('company')
        ->get();

    foreach ($rows as $r) {
        CustomerCompanyBalance::updateOrCreate(
            ['customer_id' => $r->customer_id, 'company_id' => $r->company_id],
            ['total_balance' => (int)$r->total_balance]
        );
    }

    $data = $rows->map(function ($r) use ($customerId) {
        $transactions = CustomerPoint::query()
            ->where('company_id', $r->company_id)
            ->where('customer_id', $customerId)
            ->visibleToCustomer()
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'customer_id'   => $r->customer_id,
            'company_id'    => $r->company_id,
            'total_balance' => (int)$r->total_balance,
            'company'       => $r->company,
            'transactions'  => $transactions, 
        ];
    });

    return response()->json(['data' => $data]);
}


    public function store(Request $request){
        $validated = $request->validate([
            'customer_id' => 'required|integer',
            'company_id' => 'required|exists:companies,id',
            'total_balance' => 'required|integer|min:0',
        ]);

        $totalBalance = CustomerCompanyBalance::firstOrNew([
        'customer_id' => $validated['customer_id'],
        'company_id' => $validated['company_id']
        ]);

        $totalBalance->total_balance += $validated['total_balance'];
        $totalBalance->save();

        return new CustomerCompanyBalanceResource($totalBalance);
    }

    public function show($id){
        $totalBalance = CustomerCompanyBalance::with(['customer', 'company'])->findOrFail($id);

        return new CustomerCompanyBalanceResource($totalBalance);
    }

    public function update(Request $request, $id){
        $totalBalance = CustomerCompanyBalance::findOrFail($id);

        $validated = $request->validate([
            'total_balance' => 'required|integer|min:0',
        ]);

        $totalBalance->update($validated);

        return new CustomerCompanyBalanceResource($totalBalance);
    }

    public function destroy($id){
        $totalBalance = CustomerCompanyBalance::findOrFail($id);
        $totalBalance->delete();

        return response()->json(['message' => 'Balance deleted successfully']);
    }


public function customerCompanyBalance(Request $request)
{
    $customerId = $request->query('customer_id') ?? Auth::id();
    $companyId  = $request->query('company_id');

    if (!$companyId) {
        return response()->json(['error' => 'company_id is required'], 400);
    }

    $balance = CustomerCompanyBalance::where('customer_id', $customerId)
        ->where('company_id', $companyId)
        ->value('total_balance');

    if ($balance === null) {
        $balance = CustomerPoint::where('company_id', $companyId)
            ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
            ->where('status', 'completed')
            ->sum('points_earned'); 
    }

    Log::debug('customerCompanyBalance', [
        'customerId' => $customerId,
        'companyId'  => $companyId,
        'returned_balance' => $balance
    ]);

    return response()->json([
        'total_balance' => (int) ($balance ?? 0)
    ]);
}

}

