<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApiCompanyResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'display_name' => $this->display_name,
            'company_logo' => $this->company_logo 
                ? asset('storage/'.$this->company_logo) 
                : null,
            'business_type' => $this->business_type,
            'email_contact_1' => $this->email_contact_1,
            'email_contact_2' => $this->email_contact_2,
            'telephone_contact_1' => $this->telephone_contact_1,
            'telephone_contact_2' => $this->telephone_contact_2,
            'region' => $this->region,
            'province' => $this->province,
            'city_municipality' => $this->city_municipality,
            'barangay' => $this->barangay,
            'street' => $this->street,
            'zipcode' => $this->zipcode,
            'tin_number' => $this->tin_number,
            'business_registration_number' => $this->business_registration_number,
        ];
    }
}
