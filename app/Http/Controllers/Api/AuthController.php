<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Email;
use Illuminate\Support\Str;

use function Laravel\Prompts\error;
use function Laravel\Prompts\password;

class AuthController extends Controller
{
    public function register(Request $request){
        
        try {
            DB::beginTransaction();
            $validate = Validator::make($request->all(),[
            'name'=> 'nullable|string|max:255',
            'email'=> 'required|email|unique:users',
            'password' => 'required|string|between:8, 255|confirmed'
        ]);

          if ($validate->fails()) {
            return response()->json([
                'status' => 'fail',
                'errors' => $validate->errors()
            ], 422);
        }
        
        $user = User::create([
            'name' => $request->name ?? null,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(),
            'remember_token' => Str::random(60),
        ]);

        // $token = $user->createToken($request->email);
        $token = $user->createToken('authToken')->plainTextToken;

        DB::commit();
      
        return response()->json([
            'token' => $token,
            'user' => $user,
        ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // public function login(Request $request){

    //     $validate = Validator::make($request->all(),[
    //         'email'=>'required|email|exists:users,email',
    //         'password' =>'required'
    //     ]);

    //     // $user = User::where('email',$request->email)->first();

    //     $user = User::where('email', $request->email)->first();

    //     if (! $user || ! Hash::check($request->password, $user->password)) {
    //             throw \Illuminate\Validation\ValidationException::withMessages([
    //                 'email' => ['The provided credentials are incorrect.'],
    //             ]);
    //         }

    //     if(!Hash::check($request->password, $user->password)){
    //         return [
    //             'message' => "INVALID CREDENTIALS",
    //         ];
    //     }
    //     // $token = $user->createToken($user->email);
    //     $token = $user->createToken('authToken')->plainTextToken;

    //     return response()->json([
    //         'user'  => $user->load('profile'),
    //         'token' => $token
    //     ]);
    // }


    
public function login(Request $request)
{
    $validate = Validator::make($request->all(), [
        'email'=>'required|email|exists:users,email',
        'password' =>'required'
    ]);

    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        return response()->json([
            'status'  => 'fail',
            'message' => 'Invalid credentials',
        ], 401);
    }

    $token = $user->createToken('authToken')->plainTextToken;

    return response()->json([
        'status' => 'success',
        'user'   => $user->load('profile'),
        'token'  => $token
    ]);
}


    public function logout(Request $request){

        // $request->user()->tokens()->delete();
        // return 'logged out';

        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

}

