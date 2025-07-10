<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;

use function Pest\Laravel\delete;

class CompanyController extends Controller
{
    public function index(){
        $company = Company::get();
        if($company->count() > 0){
            return CompanyResource::collection($company);
        }
        else{
            return response()->json(['message'=> 'Empty'],200);
        }
    }
    public function store(Request $request){
        
        $validate = Validator::make($request->all(),
        [
                    'company_name' => 'required|string|max:255',
                    'display_name' => 'required|string|max:255',
                    'company_logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                    'business_type' => 'required|string|max:255',

                    'telephone_contact_1' => 'required|string|max:255',
                    'telephone_contact_2' => 'nullable|string|max:255',
                    'email_contact_1' => 'required|string|max:255',
                    'email_contact_2' => 'nullable|string|max:255',

                    'barangay' => 'required|string|max:255',
                    'city_municipality' => 'required|string|max:255',
                    'province' => 'required|string|max:255',
                    'region' => 'required|string|max:255',
                    'zipcode' => 'required|string|max:255',
                    'street'  => 'required}string|max:255',
                    'country' => 'nullable|string|max:255',
                    'currency_code' => 'required|string|max:255',

                    'business_registration_number' => 'required|string|max:255',
                    'tin_number' => 'required|string|max:255',

                ]);

                if($validate->fails()){
                    return response()->json([
                    'message' => 'Validation Failed',
                    'errors' => $validate->errors()
                    ], 422); 
                }

            $validatedData = $validate->validated();

            $company = new Company($validatedData);
<<<<<<< HEAD
            $company->company_name = $validatedData['company_name'];
            $company->dispaly_name = $validatedData['display_name'];
            $company->business_type = $validatedData['business_type'];
=======
>>>>>>> refs/remotes/origin/main

            $business_number_token = Crypt::encryptString($validatedData['business_registration_number']);
            $tin_number_token = Crypt::encryptString($validatedData['tin_number']);

            $company->business_registration_number = $business_number_token ;
            $company->tin_number =  $tin_number_token;

            if ($request->hasFile('company_logo')) {
            $company_logo = $request->file('company_logo');

            $fileName = Str::uuid() . '.' . $company_logo->getClientOriginalExtension();
            $path = $company_logo->storeAs('company_logos', $fileName, 'public');

            $company->company_logo = $path; // Save the generated path to the database
        }
        $company->save();

        return new CompanyResource($company);
    }
    public function update(Request $request, Company $company){
        
        $validate = Validator::make($request->all(),
        [
                    'company_name' => 'required|string|max:255',
                    'display_name' => 'required|string|max:255',
                    'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                    'business_type' => 'required|string|max:255',

                    'telephone_contact_1' => 'required|string|max:255',
                    'telephone_contact_2' => 'nulable|string|max:255',
                    'email_contact_1' => 'required|string|max:255',
                    'email_contact_2' => 'nullable|string|max:255',

                    'barangay' => 'required|string|max:255',
                    'city_municipality' => 'required|string|max:255',
                    'province' => 'required|string|max:255',
                    'region' => 'required|string|max:255',
                    'zipcode' => 'required|string|max:255',
                    'street'  => 'required}string|max:255',
                    'country' => 'nullable|string|max:255',
                    'currency_code' => 'required|string|max:255',

                    'business_registration_number' => 'required|string|max:255',
                    'tin_number' => 'required|string|max:255',

                ]);

                if($validate->fails()){
                    return response()->json([
                    'message' => 'Validation Failed',
                    'errors' => $validate->errors()
                    ], 422); 
                }
                
                $validatedData = $validate->validated();

            $company->fill($validatedData);

                $business_number_token = Crypt::encryptString($validatedData['business_registration_number']);
                $tin_number_token = Crypt::encryptString($validatedData['tin_number']);

            $company->business_registration_number = $business_number_token;
            $company->tin_number =  $tin_number_token;

            if ($request->hasFile('company_logo')) {
            $company_logo = $request->file('company_logo');

            $fileName = Str::uuid() . '.' . $company_logo->getClientOriginalExtension();
            $path = $company_logo->storeAs('company_logos', $fileName, 'public');

            $company->company_logo = $path; // Save the generated path to the database
            }
        $company->save();

        return new CompanyResource($company);

    }
<<<<<<< HEAD
    public function show(Request $request, Company $company){
=======
    public function show(Company $company){

        $company->business_registration_number = Crypt::decryptString($company->business_registration_number);
        $company->tin_number = Crypt::decryptString($company->tin_number);
>>>>>>> refs/remotes/origin/main
        return new CompanyResource($company);
    }
    public function destroy(Company $company){

            $company->delete();
            return response()->json([
                'message'=>'Data has been deleted successfully'
            ]);

    }


}
