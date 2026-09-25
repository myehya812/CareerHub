<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobListingRequest extends FormRequest{

     public function authorize(): bool{
         
        return true;
     }




    public function rules(): array {

    return[ 
         'title' =>'required|string|max:255',
         'description' => 'required|string',
         'location' => 'required|string|max:255',
         'type' => 'required|in:Full-Time,Part-Time,Internship,Contract',
         'salary_min' => 'nullable|integer|min:0',
         'salary_max'=> 'nullable|integer|min:0|gte:salary_min',
         'currency' => 'required|string|size:3',
         'status' => 'required|in:active,draft,closed',

    ];
    }

}