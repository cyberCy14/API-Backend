<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyResource;
use App\Models\Companies;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Requests\CompanyRequest;

class CompanyController extends Controller
{
    public function index() {
        $company = Companies::get();
        if ($company->count() > 0) {
            return CompanyResource::collection($company);
        }
        return response()->json(['message'=> 'Empty'], 200);
    }

    public function store(CompanyRequest $request) {
        $validatedData = $request->validated();

        $company = new Companies($validatedData);
        $company->company_name = $validatedData['company_name'];
        $company->dispaly_name = $validatedData['display_name'];
        $company->business_type = $validatedData['business_type'];

        $company->telephone_contact_1 = $validatedData['telephone_contact_1'];
        $company->telephone_contact_2 = $validatedData['telephone_contact_2'];
        $company->email_contact_1 = $validatedData['email_contact_1'];
        $company->email_contact_2 = $validatedData['email_contact_2'];

        $company->barangay = $validatedData['barangay'];
        $company->city_municipality = $validatedData['city_municipality'];
        $company->province = $validatedData['province'];
        $company->region = $validatedData['region'];
        $company->zipcode = $validatedData['zipcode'];
        $company->country = $validatedData['country'];
        $company->registration_number = $validatedData['registration_number'];
        $company->tin_number = $validatedData['tin_number'];

        if ($request->hasFile('company_logo')) {
            $logo = $request->file('company_logo');
            $fileName = Str::uuid() . '.' . $logo->getClientOriginalExtension();
            $path = $logo->storeAs('company_logos', $fileName, 'public');
            $company->logo_path = $path;
        }

        $company->save();

        return new CompanyResource($company);
    }

    public function update(CompanyRequest $request, Companies $company) {
        $validatedData = $request->validated();

        $company->company_name = $validatedData['company_name'];
        $company->dispaly_name = $validatedData['display_name'];
        $company->business_type = $validatedData['business_type'];

        $company->telephone_contact_1 = $validatedData['telephone_contact_1'];
        $company->telephone_contact_2 = $validatedData['telephone_contact_2'];
        $company->email_contact_1 = $validatedData['email_contact_1'];
        $company->email_contact_2 = $validatedData['email_contact_2'];

        $company->barangay = $validatedData['barangay'];
        $company->city_municipality = $validatedData['city_municipality'];
        $company->province = $validatedData['province'];
        $company->region = $validatedData['region'];
        $company->zipcode = $validatedData['zipcode'];
        $company->country = $validatedData['country'];
        $company->business_registration_number = $validatedData['registration_number'];
        $company->tin_number = $validatedData['tin_number'];

        if ($request->hasFile('company_logo')) {
            $logo = $request->file('company_logo');
            $fileName = Str::uuid() . '.' . $logo->getClientOriginalExtension();
            $path = $logo->storeAs('company_logos', $fileName, 'public');
            $company->logo_path = $path;
        }

        $company->save();

        return new CompanyResource($company);
    }

    public function show(Request $request, Companies $company) {
        return new CompanyResource($company);
    }

    public function destroy(Companies $company) {
        $company->delete();
        return response()->json([
            'message'=>'Data has been deleted successfully'
        ]);
    }
}
