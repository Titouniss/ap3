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
    /********************************    USERS    **************************************/
    /***********************************************************************************/
    Route::prefix('user-management')->group(function () {
        Route::get('index', 'API\UserController@index');
        Route::get('show/{id}', 'API\UserController@show');
        Route::group(['middleware' => ['can:publish users|edit users']], function () {
            Route::post('store', 'API\UserController@store');
            Route::post('update/{id}', 'API\UserController@update');
        });
        Route::group(['middleware' => ['can:delete users']], function () {
            Route::delete('destroy/{id}', 'API\UserController@destroy');
        });
    });


    /***********************************************************************************/
    /********************************    ROLES    **************************************/
    /***********************************************************************************/
    Route::prefix('role-management')->group(function () {
        Route::get('index', 'API\RoleController@index');
        Route::get('show/{id}', 'API\RoleController@show');
        Route::group(['middleware' => ['can:publish roles|edit roles']], function () {
            Route::post('store', 'API\RoleController@store');
            Route::post('update/{id}', 'API\RoleController@update');
        });
        Route::group(['middleware' => ['can:delete roles']], function () {
            Route::delete('destroy/{id}', 'API\RoleController@destroy');
        });
    });

    /***********************************************************************************/
    /********************************    PERMISSIONS  **********************************/
    /***********************************************************************************/
    Route::prefix('permission-management')->group(function () {
        Route::get('index', 'API\PermissionController@index');
        Route::get('show/{id}', 'API\PermissionController@show');
        Route::group(['middleware' => ['can:publish permissions|edit permissions']], function () {
            Route::post('store', 'API\PermissionController@store');
            Route::post('update/{id}', 'API\PermissionController@update');
        });
        Route::group(['middleware' => ['can:delete permissions']], function () {
            Route::delete('destroy/{id}', 'API\PermissionController@destroy');
        });
    });

    /***********************************************************************************/
    /********************************    COMPANIES **************************************/
    /***********************************************************************************/
    Route::prefix('company-management')->group(function () {
        Route::get('index', 'API\CompanyController@index');
        Route::get('show/{id}', 'API\CompanyController@show');
        // Route::group(['middleware' => ['can:publish companies']], function () {
            Route::post('store', 'API\CompanyController@store');
            Route::post('update/{id}', 'API\CompanyController@update');
        // });
        // Route::group(['middleware' => ['can:delete roles']], function () {
            Route::delete('{id}', 'API\CompanyController@destroy');
        // });
    });

    /***********************************************************************************/
    /*********************************   SKILLS   **************************************/
    /***********************************************************************************/
    Route::prefix('skill-management')->group(function () {
        Route::get('index', 'API\SkillController@index');
        Route::get('show/{id}', 'API\SkillController@show');
        // Route::group(['middleware' => ['can:publish companies']], function () {
            Route::post('store', 'API\SkillController@store');
            Route::post('update/{id}', 'API\SkillController@update');
        // });
        // Route::group(['middleware' => ['can:delete roles']], function () {
            Route::delete('{id}', 'API\SkillController@destroy');
        // });
    });

    /***********************************************************************************/
    /*******************************   WORKAREAS   *************************************/
    /***********************************************************************************/
    Route::prefix('workarea-management')->group(function () {
        Route::get('index', 'API\WorkareaController@index');
        Route::get('show/{id}', 'API\WorkareaController@show');
        // Route::group(['middleware' => ['can:publish companies']], function () {
            Route::post('store', 'API\WorkareaController@store');
            Route::post('update/{id}', 'API\WorkareaController@update');
        // });
        // Route::group(['middleware' => ['can:delete roles']], function () {
            Route::delete('{id}', 'API\WorkareaController@destroy');
        // });
    });
});

