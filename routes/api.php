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

Route::group(['middleware' => 'auth:api'], function () {
    /***********************************************************************************/
    /********************************    USERS    **************************************/
    /***********************************************************************************/
    Route::prefix('user-management')->group(function () {
        Route::group(['middleware' => ['can:read users']], function () {
            Route::get('index', 'API\UserController@index');
        });
        Route::group(['middleware' => ['can:publish users']], function () {
            Route::post('store', 'API\UserController@store');
        });
        Route::group(['middleware' => ['can:edit,user']], function () {
            Route::get('show/{user}', 'API\UserController@show');
            Route::post('update/{user}', 'API\UserController@update');
            Route::post('updateAccount/{user}', 'API\UserController@updateAccount');
            Route::post('updatePassword/{user}', 'API\UserController@updatePassword');
            Route::post('updateWorkHours/{user}', 'API\UserController@updateWorkHours');
        });
        Route::group(['middleware' => ['can:delete users']], function () {
            Route::delete('destroy/{id}', 'API\UserController@destroy');
            Route::delete('forceDelete/{id}', 'API\UserController@forceDelete');
        });
    });


    /***********************************************************************************/
    /********************************    ROLES    **************************************/
    /***********************************************************************************/
    Route::prefix('role-management')->group(function () {
        Route::group(['middleware' => ['permission:read roles']], function () {
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
        Route::group(['middleware' => ['can:read permissions']], function () {
            Route::get('index', 'API\PermissionController@index');
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
    /********************************   COMPANIES **************************************/
    /***********************************************************************************/
    Route::prefix('company-management')->group(function () {
        Route::group(['middleware' => ['can:read companies']], function () {
            Route::get('index', 'API\CompanyController@index');
            Route::get('show/{id}', 'API\CompanyController@show');
        });
        Route::group(['middleware' => ['can:publish companies']], function () {
            Route::post('store', 'API\CompanyController@store');
        });
        Route::group(['middleware' => ['can:edit companies']], function () {
            Route::post('update/{id}', 'API\CompanyController@update');
            Route::put('restore/{id}', 'API\CompanyController@restore');
        });
        Route::group(['middleware' => ['can:delete companies']], function () {
            Route::delete('destroy/{id}', 'API\CompanyController@destroy');
            Route::delete('forceDelete/{id}', 'API\CompanyController@forceDelete');
        });
    });

    /***********************************************************************************/
    /*********************************   SKILLS   **************************************/
    /***********************************************************************************/
    Route::prefix('skill-management')->group(function () {
        Route::group(['middleware' => ['can:read skills']], function () {
            Route::get('index', 'API\SkillController@index');
            Route::get('show/{id}', 'API\SkillController@show');
            Route::get('getByTaskId/{id}', 'API\SkillController@getByTaskId');
        });
        Route::group(['middleware' => ['can:publish skills']], function () {
            Route::post('store', 'API\SkillController@store');
        });
        Route::group(['middleware' => ['can:edit skills']], function () {
            Route::post('update/{id}', 'API\SkillController@update');
        });
        Route::group(['middleware' => ['can:delete skills']], function () {
            Route::delete('destroy/{id}', 'API\SkillController@destroy');
            Route::delete('forceDelete/{id}', 'API\SkillController@forceDelete');
        });
    });

    /***********************************************************************************/
    /*******************************   WORKAREAS   *************************************/
    /***********************************************************************************/
    Route::prefix('workarea-management')->group(function () {
        Route::group(['middleware' => ['can:read workareas']], function () {
            Route::get('index', 'API\WorkareaController@index');
            Route::get('show/{id}', 'API\WorkareaController@show');
        });
        Route::group(['middleware' => ['can:publish workareas']], function () {
            Route::post('store', 'API\WorkareaController@store');
        });
        Route::group(['middleware' => ['can:edit workareas']], function () {
            Route::post('update/{id}', 'API\WorkareaController@update');
            Route::put('restore/{id}', 'API\WorkareaController@restore');
        });
        Route::group(['middleware' => ['can:delete workareas']], function () {
            Route::delete('destroy/{id}', 'API\WorkareaController@destroy');
            Route::delete('forceDelete/{id}', 'API\WorkareaController@forceDelete');
        });
    });

    /***********************************************************************************/
    /********************************   PROJETCS   *************************************/
    /***********************************************************************************/
    Route::prefix('project-management')->group(function () {
        Route::group(['middleware' => ['can:read projects']], function () {
            Route::get('index', 'API\ProjectController@index');
            Route::get('show/{id}', 'API\ProjectController@show');
        });
        Route::group(['middleware' => ['can:publish projects']], function () {
            Route::post('store', 'API\ProjectController@store');
        });
        Route::group(['middleware' => ['can:edit projects']], function () {
            Route::get('start/{id}', 'API\ProjectController@start');
            Route::post('store-range/{id}', 'API\ProjectController@addRange');
            Route::post('update/{id}', 'API\ProjectController@update');
            Route::put('restore/{id}', 'API\ProjectController@restore');

        });
        Route::group(['middleware' => ['can:delete projects']], function () {
            Route::delete('destroy/{id}', 'API\ProjectController@destroy');
            Route::delete('forceDelete/{id}', 'API\ProjectController@forceDelete');
        });
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
            Route::put('restore/{id}', 'API\RangeController@restore');
        });
        Route::group(['middleware' => ['can:delete ranges']], function () {
            Route::delete('destroy/{id}', 'API\RangeController@destroy');
            Route::delete('forceDelete/{id}', 'API\RangeController@forceDelete');
        });
    });

    /***********************************   TASK   **************************************/
    /***********************************************************************************/
    Route::prefix('task-management')->group(function () {
        Route::group(['middleware' => ['can:read tasks']], function () {
            Route::get('index', 'API\TaskController@index');
            Route::get('bundle/{id}', 'API\TaskController@getByBundle');
            Route::get('show/{id}', 'API\TaskController@show');
        });
        Route::group(['middleware' => ['can:publish tasks']], function () {
            Route::post('store', 'API\TaskController@store');
        });
        Route::group(['middleware' => ['can:publish tasks']], function () {
            Route::post('store-comment/{id}', 'API\TaskController@addComment');
            Route::post('update-partial/{id}', 'API\TaskController@updatePartial');
            Route::post('update/{id}', 'API\TaskController@update');
        });
        Route::group(['middleware' => ['can:delete tasks']], function () {
            Route::delete('{id}', 'API\TaskController@destroy');
        });
    });

    /*****************************   REPETITIVE - TASK   ********************************/
    /***********************************************************************************/
    Route::prefix('repetitive-task-management')->group(function () {
        Route::group(['middleware' => ['can:read tasks']], function () {
            Route::get('range/{id}', 'API\RangeController@getRepetitiveTasks');
        });
    });

    /***********************************************************************************/
    /*****************************   UNAVAILABILITIES   ********************************/
    /***********************************************************************************/
    Route::prefix('unavailability-management')->group(function () {
        Route::group(['middleware' => ['can:read unavailabilities']], function () {
            Route::get('index', 'API\UnavailabilityController@index');
            Route::get('show/{id}', 'API\UnavailabilityController@show');
        });
        Route::group(['middleware' => ['can:publish unavailabilities']], function () {
            Route::post('store', 'API\UnavailabilityController@store');
        });
        Route::group(['middleware' => ['can:edit unavailabilities']], function () {
            Route::post('update/{id}', 'API\UnavailabilityController@update');
        });
        Route::group(['middleware' => ['can:delete unavailabilities']], function () {
            Route::delete('destroy/{id}', 'API\UnavailabilityController@destroy');
        });
    });

    /***********************************************************************************/
    /***********************************   Hours   *************************************/
    /***********************************************************************************/
    Route::prefix('hours-management')->group(function () {
        Route::group(['middleware' => ['can:read hours']], function () {
            Route::get('index', 'API\HoursController@index');
            Route::get('show/{id}', 'API\HoursController@show');
        });
        Route::group(['middleware' => ['can:publish hours']], function () {
            Route::post('store', 'API\HoursController@store');
        });
        Route::group(['middleware' => ['can:edit hours']], function () {
            Route::post('update/{id}', 'API\HoursController@update');
        });
        Route::group(['middleware' => ['can:delete hours']], function () {
            Route::delete('destroy/{id}', 'API\HoursController@destroy');
        });
    });

    /***********************************************************************************/
    /***********************************   Dealing Hours   *************************************/
    /***********************************************************************************/
    Route::prefix('dealing-hours-management')->group(function () {
        Route::group(['middleware' => ['can:read dealingHours']], function () {
            Route::get('index', 'API\DealingHoursController@index');
            Route::get('show/{id}', 'API\DealingHoursController@show');
            Route::get('overtimesYear/{year}/{id}', 'API\DealingHoursController@getOvertimesByYear');
        });
        Route::group(['middleware' => ['can:publish usedHours']], function () {
            Route::post('store', 'API\DealingHoursController@store');
            Route::post('storeOrUpdateUsed', 'API\DealingHoursController@storeOrUpdateUsed');
        });
        Route::group(['middleware' => ['can:edit usedHours']], function () {
            Route::post('update/{id}', 'API\DealingHoursController@update');
        });
        Route::group(['middleware' => ['can:delete usedHours']], function () {
            Route::delete('destroy/{id}', 'API\DealingHoursController@destroy');
            Route::delete('forceDelete/{id}', 'API\DealingHoursController@forceDelete');
        });
    });

    /***********************************************************************************/
    /***********************************   Customers   *************************************/
    /***********************************************************************************/
    Route::prefix('customer-management')->group(function () {
        Route::group(['middleware' => ['can:read customers']], function () {
            Route::get('index', 'API\CustomersController@index');
            Route::get('show/{id}', 'API\CustomersController@show');
        });
        Route::group(['middleware' => ['can:publish customers']], function () {
            Route::post('store', 'API\CustomersController@store');
        });
        Route::group(['middleware' => ['can:edit customers']], function () {
            Route::post('update/{id}', 'API\CustomersController@update');
            Route::put('restore/{id}', 'API\CustomersController@restore');
        });
        Route::group(['middleware' => ['can:delete customers']], function () {
            Route::delete('destroy/{id}', 'API\CustomersController@destroy');
            Route::delete('forceDelete/{id}', 'API\CustomersController@forceDelete');
        });
    });
});
