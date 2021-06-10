<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\BalanceHistory;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

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


//        $user->update($request->all());

        $user->balance += $request->amount;
        $user->update();

        $transaction = new BalanceHistory();

        $transaction->recipient_user_ID = $id;
        $transaction->transaction_time = Carbon::now()->toDateTimeString();
        $transaction->transaction_amount = $request->amount;

        $transaction->save();


        return response(['user' => $user, 'message' => 'Success'], 200);

    }

    public function balance_history(Request $request) {

        $data = $request->all();

        $validator = Validator::make($data, [
            'user_id' => 'required'
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
        $recipient_user_ID = $id;
        $transactions = BalanceHistory::query()->where('recipient_user_ID', $recipient_user_ID)->get();


        return response(['transactions' => $transactions, 'message' => 'Success'], 200);
    }

    public function transfer(Request $request , $user_id , $amount) {

        $data = $request->validate([
            'email' => 'email|required',
            'password' => 'required',
//            'amount' => 'required|numeric|between:0,999.99'

        ]);

//        $dataValidator = $request->all();
//
//        $validator = Validator::make($dataValidator, [
//            'amount' => 'required|numeric|between:0,999.99'
//        ]);

//        $id = $request->user_id;
        $id = $user_id;
        $user = User::find($id);

        if (!auth()->attempt($data)) {
            return response(['error_message' => 'Incorrect Details.
            Please try again']);
        }
//        elseif ($user == null) {
//            return response(['error_message' => 'User not found'], 404);
//        }
//        elseif ($validator->fails()){
//            return response(['error' => $validator->errors(),
//                'Validation Error'], 422);
//        }


        $token = auth()->user()->createToken('API Token')->accessToken;

//        $user->update($request->all());

        $id = auth()->user()->id;
        $auth_user = User::find($id);
//        $auth_user->balance -= $request->amount;
        $auth_user->balance -= $amount;
        $auth_user->update();

//        $user->balance += ($request->amount)*99/100;
        $user->balance += ($amount)*99/100;
        $user->update();


        return response(['user' => auth()->user(), 'token' => $token]);

    }


}
