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
    Route::get('user', 'API\UserController@getUserByToken');
    Route::get('user/registration/{token}', 'API\UserController@getUserForRegistration');

    Route::post('logout', 'API\UserController@logout');
    Route::post('register', 'API\UserController@register');
    Route::post('register/{token}', 'API\UserController@registerWithToken');
    
    // handle reset password form process
    Route::post('forget', 'Auth\ForgotPasswordController@getResetToken');
    Route::get('password/reset/{token}/{email}', 'Auth\ResetPasswordController@reset')->name('password.reset')->middleware('signed');
    Route::post('reset/password', 'Auth\ResetPasswordController@callResetPassword');

    Route::get('email/verify/{id}/{hash}', 'API\UserController@verify')->name('api.verification.verify')->middleware('signed');
    Route::post('email/resend', 'API\UserController@resendVerification');
});

/***********************************************************************************/
/*****************************    AUTHENTICATED ************************************/
/***********************************************************************************/

Route::group(['middleware' => 'auth:api'], function(){
    /***********************************************************************************/
    /********************************    USERS    **************************************/
    /***********************************************************************************/
    Route::prefix('user-management')->group(function () {
        Route::group(['middleware' => ['can:read users']], function () {
            Route::get('index', 'API\UserController@index');
            Route::get('show/{id}', 'API\UserController@show');
        });
        Route::group(['middleware' => ['can:publish users']], function () {
            Route::post('store', 'API\UserController@store');
        });
        Route::group(['middleware' => ['can:edit users']], function () {
            Route::post('update/{id}', 'API\UserController@update');
            Route::post('updateAccount/{id}', 'API\UserController@updateAccount');
            Route::post('updateInformation/{id}', 'API\UserController@updateInformation');
            Route::post('updatePassword', 'API\UserController@updatePassword');
        });
        Route::group(['middleware' => ['can:delete users']], function () {
            Route::delete('destroy/{id}', 'API\UserController@destroy');
        });
    });


    /***********************************************************************************/
    /********************************    ROLES    **************************************/
    /***********************************************************************************/
    Route::prefix('role-management')->group(function () {
        Route::group(['middleware' => ['permission:read roles|publish users']], function () {
            Route::get('index', 'API\RoleController@index');
            Route::get('show/{id}', 'API\RoleController@show');
        });
        Route::group(['middleware' => ['can:publish roles']], function () {
            Route::post('store', 'API\RoleController@store');
        });
        Route::group(['middleware' => ['can:edit roles']], function () {
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
        Route::group(['middleware' => ['can:read permissions']], function () {
            Route::get('show/{id}', 'API\PermissionController@show');
        });
        Route::group(['middleware' => ['can:publish permissions']], function () {
            Route::post('store', 'API\PermissionController@store');
        });
        Route::group(['middleware' => ['can:edit permissions']], function () {
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

    /***********************************************************************************/
    /********************************   PROJETCS   *************************************/
    /***********************************************************************************/
    Route::prefix('project-management')->group(function () {
        Route::get('index', 'API\ProjectController@index');
        Route::get('show/{id}', 'API\ProjectController@show');
        // Route::group(['middleware' => ['can:publish companies']], function () {
            Route::post('store', 'API\ProjectController@store');
            Route::post('update/{id}', 'API\ProjectController@update');
        // });
        // Route::group(['middleware' => ['can:delete roles']], function () {
            Route::delete('{id}', 'API\ProjectController@destroy');
        // });
    });

    /***********************************************************************************/
    /********************************    RANGES    **************************************/
    /***********************************************************************************/
    Route::prefix('range-management')->group(function () {
        Route::group(['middleware' => ['permission:read ranges']], function () {
            Route::get('index', 'API\RangeController@index');
            Route::get('show/{id}', 'API\RangeController@show');
        });
        Route::group(['middleware' => ['can:publish ranges']], function () {
            Route::post('store', 'API\RangeController@store');
        });
        Route::group(['middleware' => ['can:edit ranges']], function () {
            Route::post('update/{id}', 'API\RangeController@update');
        });
        Route::group(['middleware' => ['can:delete ranges']], function () {
            Route::delete('destroy/{id}', 'API\RangeController@destroy');
        });
    });

    /***********************************   TASK   **************************************/
    /***********************************************************************************/
    Route::prefix('task-management')->group(function () {
        Route::get('index', 'API\TaskController@index');
        Route::get('bundle/{id}', 'API\TaskController@getByBundle');
        Route::get('show/{id}', 'API\TaskController@show');
        // Route::group(['middleware' => ['can:publish companies']], function () {
            Route::post('store', 'API\TaskController@store');
            Route::post('store-comment/{id}', 'API\TaskController@addComment');
            Route::post('update/{id}', 'API\TaskController@update');
        // });
        // Route::group(['middleware' => ['can:delete roles']], function () {
            Route::delete('{id}', 'API\TaskController@destroy');
        // });
    });

    /***********************************   TASK   **************************************/
    /***********************************************************************************/
    Route::prefix('repetitive-task-management')->group(function () {
        Route::get('range/{id}', 'API\RangeController@getRepetitiveTasks');

    });
});
