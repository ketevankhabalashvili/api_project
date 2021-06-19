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
        $validator = Validator::make($request->all(),[
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users',
            'is_admin' => 'integer|between:0,1',
            'password' => 'required|confirmed',
            'password_confirmation' => 'required|same:password',
            'balance' => 'required|numeric|between:0,999.99'
        ]);

        if ($validator->fails()) {
            return response()->json(['error_message' => $validator->errors()->first()], 422);
        }

        $data = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users',
            'is_admin' => 'integer|between:0,1',
            'password' => 'required|confirmed',
            'password_confirmation' => 'required|same:password',
            'balance' => 'required|numeric|between:0,999.99'
        ]);

        if (!auth()->attempt($data)) {
            return response(['error_message' => 'Incorrect Details.
            Please try again'], 422);
        }

        $data['password'] = bcrypt($request->password);

        $user = User::create($data);

        $token = $user->createToken('API Token')->accessToken;

        return response([ 'user' => $user, 'token' => $token]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'email' => 'email|required',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['error_message' => $validator->errors()->first()], 422);
        }

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


        if ($validator->fails()){
            return response(['error_message' => $validator->errors()->first()], 422);
        }
        elseif ($user == null) {
            return response(['error_message' => 'User not found'], 404);
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

        if ($validator->fails()){
            return response(['error_message' => $validator->errors()->first()], 422);
        }
        elseif ($user == null) {
            return response(['error_message' => 'User not found'], 404);
        }
        $recipient_user_ID = $id;
        $transactions = BalanceHistory::query()->where('recipient_user_ID', $recipient_user_ID)->get();


        return response(['transactions' => $transactions, 'message' => 'Success']);
    }

    public function transfer(Request $request , $user_id ) {

        $dataValidator = $request->all();



        $password_email_validator = Validator::make($dataValidator, [
            'email' => 'email|required',
            'password' => 'required',
        ]);

        if ($password_email_validator->fails()) {
            return response(['error_message' => $password_email_validator->errors()->first()], 422);
        }


        $data = $request->validate([
            'email' => 'email|required',
            'password' => 'required',
        ]);


        $validator = Validator::make($dataValidator, [
            'amount' => 'required|numeric|between:0,999.99'
        ]);

        $id = $user_id;
        $user = User::find($id);

        if (!auth()->attempt($data)) {
            return response(['error_message' => 'Incorrect Details. Please try again'], 422);
        }
        elseif ($user == null) {
            return response(['error_message' => 'User not found'], 404);
        }
        elseif ($validator->fails()){
            return response(['error_message' => $validator->errors()->first()], 422);
        }



        $token = auth()->user()->createToken('API Token')->accessToken;

        $id = auth()->user()->id;
        $auth_user = User::find($id);
        $auth_user->balance -= $request->amount;
        $auth_user->update();

        $user->balance += ($request->amount)*99/100;
        $user->update();

        $transaction = new Transaction();
        $transaction->sender_user_id = $id;
        $transaction->recipient_user_id = $user_id;
        $transaction->amount = $request->amount;
        $transaction->commission_amount	= ($request->amount)*1/100;

        $transaction->save();



        return response(['message' => 'Successful transfer']);

    }

    public function my_transactions(Request $request) {

        $validator = Validator::make($request->all(), [
            'email' => 'email|required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response(['error_message' => $validator->errors()->first()], 422);
        }

        $data = $request->validate([
            'email' => 'email|required',
            'password' => 'required',
        ]);


        if (!auth()->attempt($data)) {
            return response(['error_message' => 'Incorrect Details.
            Please try again'], 422);
        }

        $token = auth()->user()->createToken('API Token')->accessToken;

        $id = auth()->user()->id;


        $transaction = Transaction::where('sender_user_id', $id)->distinct()->get();

        return response(['message' => $transaction]);

    }

    public function transactions(Request $request) {

        $validator = Validator::make($request->all(), [
            'email' => 'email|required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response(['error_message' => $validator->errors()->first()], 422);
        }

        $data = $request->validate([
            'email' => 'email|required',
            'password' => 'required',
        ]);


        if (!auth()->attempt($data)) {
            return response(['error_message' => 'Incorrect Details.
            Please try again'], 422);
        }

        $token = auth()->user()->createToken('API Token')->accessToken;

        $is_admin = auth()->user()->is_admin;

        $transaction = Transaction::all();
        $commission_sum = Transaction::sum('commission_amount');

        if ($is_admin == 1) {
            return response(['message' => ['transaction_list' => $transaction, 'commission_sum' => $commission_sum]]);
        }
        else {
            return response(['message' => 'Access Denied']);
        }

    }


}
