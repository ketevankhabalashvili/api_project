<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserAuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
            'balance' => 'required|numeric|between:0,999.99'
        ]);

        $data['password'] = bcrypt($request->password);

        $user = User::create($data);

        $token = $user->createToken('API Token')->accessToken;

        return response([ 'user' => $user, 'token' => $token]);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'email|required',
            'password' => 'required'
        ]);

        if (!auth()->attempt($data)) {
            return response(['error_message' => 'Incorrect Details.
            Please try again']);
        }

        $token = auth()->user()->createToken('API Token')->accessToken;

        return response(['user' => auth()->user(), 'token' => $token]);

    }

    public function fill_balance(Request $request)
    {

        $data = $request->all();

        $validator = Validator::make($data, [
            'user_id' => 'required',
            'amount' => 'required|numeric|between:0,999.99'
        ]);

        $id = $request->user_id;
        $user = User::find($id);

        if ($user == null) {
            return response(['error_message' => 'User not found'], 404);
        }
        elseif ($validator->fails()){
            return response(['error' => $validator->errors(),
                'Validation Error'], 422);
        }


        $user->update($request->all());

        $user->balance += $request->amount;
        $user->update();


        return response(['user' => $user, 'message' => 'Success'], 200);

    }
}
