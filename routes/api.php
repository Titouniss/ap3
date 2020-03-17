<?php

use Illuminate\Http\Request;

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



Route::prefix('auth')->group(function () {
    /***********************************************************************************/
    /********************************    USERS    **************************************/
    /***********************************************************************************/
    Route::post('login', 'API\UserController@login');
    Route::post('logout', 'API\UserController@logout');
    Route::post('register', 'API\UserController@register');
    Route::post('forget', 'Auth\ForgotPasswordController@getResetToken');
    Route::post('password/reset', 'Auth\ResetPasswordController@reset');
    Route::get('email/verify/{token}', 'Auth\VerificationController@verify');

    /***********************************************************************************/
    /********************************    OTHERS   **************************************/
    /***********************************************************************************/
    // Route::group(['middleware' => 'auth:api'], function(){
    //     Route::get('user', 'AuthController@user');
    //     Route::post('logout', 'AuthController@logout');
    // });
});