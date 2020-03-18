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
    /********************************    USER     **************************************/
    /***********************************************************************************/
    Route::post('login', 'API\UserController@login');
    Route::post('logout', 'API\UserController@logout');
    Route::post('register', 'API\UserController@register');
    
    // handle reset password form process
    Route::post('forget', 'Auth\ForgotPasswordController@getResetToken');
    Route::get('password/reset/{token}/{email}', 'Auth\ResetPasswordController@reset')->name('password.reset');
    Route::post('reset/password', 'Auth\ResetPasswordController@callResetPassword');
    
    Route::get('email/verify/{token}', 'Auth\VerificationController@verify');
});

/***********************************************************************************/
/*****************************    AUTHENTICATED ************************************/
/***********************************************************************************/

Route::group(['middleware' => 'auth:api'], function(){
    
    /***********************************************************************************/
    /********************************    USER     **************************************/
    /***********************************************************************************/
    Route::prefix('user-management')->group(function () {
        Route::get('users', 'API\UserController@index');
        Route::group(['middleware' => ['can:publish users']], function () {
            Route::post('create/user', 'API\UserController@create');
        });
    });
});

