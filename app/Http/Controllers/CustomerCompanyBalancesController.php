<?php

namespace App\Http\Controllers;
use App\Models\CustomerCompanyBalance;
use App\Models\CustomerPoint;
use Illuminate\Http\Request;
use App\Http\Resources\CustomerCompanyBalanceResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CustomerCompanyBalancesController extends Controller
{
public function index(Request $request)
{
    $customerId = Auth::id();

    $totalBalances = CustomerPoint::select('company_id', 'customer_id', 'balance')
        ->with(['company' => function ($q) use ($customerId) {
            $q->with(['customerPoints' => function ($qp) use ($customerId) {
                $qp->where('customer_id', $customerId)
                    ->orderBy('created_at', 'desc');
            }]);
        }])
        ->when($customerId, function ($query) use ($customerId) {
            $query->where('customer_id', $customerId);
        })
        ->whereIn('id', function ($subQuery) use ($customerId) {
            $subQuery->selectRaw('MAX(id)')
                ->from('customer_points')
                ->when($customerId, function ($subQ) use ($customerId) {
                    $subQ->where('customer_id', $customerId);
                })
                ->groupBy('company_id', 'customer_id');
        })
        ->get();

    foreach ($totalBalances as $balance) {
        if (!is_null($balance->customer_id)) {
            CustomerCompanyBalance::updateOrCreate(
                [
                    'customer_id' => $balance->customer_id,
                    'company_id'  => $balance->company_id
                ],
                [
                    'total_balance' => $balance->balance
                ]
            );
        }
    }

    $data = $totalBalances->map(function ($balance) use ($customerId) {
        return [
            'customer_id'   => $balance->customer_id,
            'company_id'    => $balance->company_id,
            'total_balance' => $balance->balance,
            'company'       => $balance->company,
            'transactions'  => $balance->company->customerPoints ?? []
        ];
    });

    return response()->json([
        'data' => $data
    ]);
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


}

