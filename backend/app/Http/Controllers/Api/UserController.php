<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function store(Request $request){
      $request -> validate([
        'fname' => 'required|string|max: 255',
        'mname' => 'nullable|string|max: 255',
        'lname' => 'required|string|max: 255',
        'sname' => 'nullable|string|max: 255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|confirmed|min:8',
      ]);
    }
    public function show(){

    }
    public function update(){

    }
    public function destroy(){

    }
}
