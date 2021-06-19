<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/register', 'App\Http\Controllers\Auth\UserAuthController@register');
Route::post('/login', 'App\Http\Controllers\Auth\UserAuthController@login');
Route::post('/fill-balance', 'App\Http\Controllers\Auth\UserAuthController@fill_balance');
Route::post('/balance/history', 'App\Http\Controllers\Auth\UserAuthController@balance_history');
Route::post('/transfer/{user_id}', 'App\Http\Controllers\Auth\UserAuthController@transfer');
Route::post('/my-transactions', 'App\Http\Controllers\Auth\UserAuthController@my_transactions');
Route::post('/transactions', 'App\Http\Controllers\Auth\UserAuthController@transactions');


Route::apiResource('/user', 'UserController')->middleware('auth:api');
