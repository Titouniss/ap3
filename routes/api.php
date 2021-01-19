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

Route::prefix('auth')->group(function () {
    /***********************************************************************************/
    /********************************    USER     **************************************/
    /***********************************************************************************/
    Route::post('checkUsernamePwdBeforeLogin', 'API\UserController@checkUsernamePwdBeforeLogin');
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
    Route::post('updatePasswordBeforeLogin', 'API\UserController@updatePasswordBeforeLogin');

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
        Route::group(['middleware' => ["can:read,App\User"]], function () {
            Route::get('index', 'API\UserController@index');
        });
        Route::group(['middleware' => ["can:show,user"]], function () {
            Route::get('show/{user}', 'API\UserController@show');
        });
        Route::group(['middleware' => ['can:publish,App\User']], function () {
            Route::post('store', 'API\UserController@store');
        });
        Route::group(['middleware' => ['can:edit,user']], function () {
            Route::post('update/{user}', 'API\UserController@update');
            Route::post('updateAccount/{user}', 'API\UserController@updateAccount');
            Route::post('updatePassword/{user}', 'API\UserController@updatePassword');
            Route::post('updateWorkHours/{user}', 'API\UserController@updateWorkHours');
        });
        Route::group(['middleware' => ['can:delete,user']], function () {
            Route::put('restore/{user}', 'API\UserController@restore');
            Route::delete('destroy/{user}', 'API\UserController@destroy');
            Route::delete('forceDelete/{user}', 'API\UserController@forceDelete');
        });
    });


    /***********************************************************************************/
    /********************************    ROLES    **************************************/
    /***********************************************************************************/
    Route::prefix('role-management')->group(function () {
        Route::group(['middleware' => ['can:read,App\Models\Role']], function () {
            Route::get('index', 'API\RoleController@index');
        });
        Route::group(['middleware' => ['can:show,role']], function () {
            Route::get('show/{role}', 'API\RoleController@show');
        });
        Route::group(['middleware' => ['can:publish,App\Models\Role']], function () {
            Route::post('store', 'API\RoleController@store');
        });
        Route::group(['middleware' => ['can:edit,role']], function () {
            Route::post('update/{role}', 'API\RoleController@update');
        });
        Route::group(['middleware' => ['can:delete,role']], function () {
            Route::put('restore/{role}', 'API\RoleController@restore');
            Route::delete('destroy/{role}', 'API\RoleController@destroy');
            Route::delete('forceDelete/{role}', 'API\RoleController@forceDelete');
        });
    });

    /***********************************************************************************/
    /********************************    PERMISSIONS  **********************************/
    /***********************************************************************************/
    Route::prefix('permission-management')->group(function () {
        Route::group(['middleware' => ['can:read,Spatie\Permission\Models\Permission']], function () {
            Route::get('index', 'API\PermissionController@index');
        });
        Route::group(['middleware' => ['can:show,permission']], function () {
            Route::get('show/{permission}', 'API\PermissionController@show');
        });
        Route::group(['middleware' => ['can:publish,Spatie\Permission\Models\Permission']], function () {
            Route::post('store', 'API\PermissionController@store');
        });
        Route::group(['middleware' => ['can:edit,permission']], function () {
            Route::post('update/{permission}', 'API\PermissionController@update');
        });
        Route::group(['middleware' => ['can:delete,permission']], function () {
            Route::delete('destroy/{permission}', 'API\PermissionController@destroy');
        });
    });

    /***********************************************************************************/
    /********************************   COMPANIES **************************************/
    /***********************************************************************************/
    Route::prefix('company-management')->group(function () {
        Route::get('index', 'API\CompanyController@index');
        Route::get('show/{id}', 'API\CompanyController@show');
        Route::post('store', 'API\CompanyController@store');
        Route::put('update/{id}', 'API\CompanyController@update');
        Route::put('restore/{id}', 'API\CompanyController@restore');
        Route::delete('destroy/{id}', 'API\CompanyController@destroy');
        Route::delete('forceDelete/{id}', 'API\CompanyController@forceDelete');
    });

    /***********************************************************************************/
    /*********************************   SKILLS   **************************************/
    /***********************************************************************************/
    Route::prefix('skill-management')->group(function () {
        Route::group(['middleware' => ['can:read,App\Models\Skill']], function () {
            Route::get('index', 'API\SkillController@index');
            Route::get('getByTaskId/{task}', 'API\SkillController@getByTaskId');
        });
        Route::group(['middleware' => ['can:show,skill']], function () {
            Route::get('show/{skill}', 'API\SkillController@show');
        });
        Route::group(['middleware' => ['can:publish,App\Models\Skill']], function () {
            Route::post('store', 'API\SkillController@store');
        });
        Route::group(['middleware' => ['can:edit,skill']], function () {
            Route::post('update/{skill}', 'API\SkillController@update');
        });
        Route::group(['middleware' => ['can:delete,skill']], function () {
            Route::put('restore/{skill}', 'API\SkillController@restore');
            Route::delete('destroy/{skill}', 'API\SkillController@destroy');
            Route::delete('forceDelete/{skill}', 'API\SkillController@forceDelete');
        });
    });

    /***********************************************************************************/
    /*******************************   WORKAREAS   *************************************/
    /***********************************************************************************/
    Route::prefix('workarea-management')->group(function () {
        Route::group(['middleware' => ['can:read,App\Models\Workarea']], function () {
            Route::get('index', 'API\WorkareaController@index');
        });
        Route::group(['middleware' => ['can:show,workarea']], function () {
            Route::get('show/{workarea}', 'API\WorkareaController@show');
        });
        Route::group(['middleware' => ['can:publish,App\Models\Workarea']], function () {
            Route::post('store', 'API\WorkareaController@store');
        });
        Route::group(['middleware' => ['can:edit,workarea']], function () {
            Route::post('update/{workarea}', 'API\WorkareaController@update');
        });
        Route::group(['middleware' => ['can:delete,workarea']], function () {
            Route::put('restore/{workarea}', 'API\WorkareaController@restore');
            Route::delete('destroy/{workarea}', 'API\WorkareaController@destroy');
            Route::delete('forceDelete/{workarea}', 'API\WorkareaController@forceDelete');
        });
    });

    /***********************************************************************************/
    /********************************   PROJETCS   *************************************/
    /***********************************************************************************/
    Route::prefix('project-management')->group(function () {
        Route::group(['middleware' => ['can:read,App\Models\Project']], function () {
            Route::get('index', 'API\ProjectController@index');
        });
        Route::group(['middleware' => ['can:show,project']], function () {
            Route::get('show/{project}', 'API\ProjectController@show');
        });
        Route::group(['middleware' => ['can:publish,App\Models\Project']], function () {
            Route::post('store', 'API\ProjectController@store');
            Route::post('start', 'API\ProjectController@start');
            Route::post('store-range/{id}', 'API\ProjectController@addRange');
        });
        Route::group(['middleware' => ['can:edit,project']], function () {
            Route::post('update/{project}', 'API\ProjectController@update');
        });
        Route::group(['middleware' => ['can:delete,project']], function () {
            Route::put('restore/{project}', 'API\ProjectController@restore');
            Route::delete('destroy/{project}', 'API\ProjectController@destroy');
            Route::delete('forceDelete/{project}', 'API\ProjectController@forceDelete');
        });
    });

    /***********************************************************************************/
    /********************************    RANGES    **************************************/
    /***********************************************************************************/
    Route::prefix('range-management')->group(function () {
        Route::group(['middleware' => ['can:read,App\Models\Range']], function () {
            Route::get('index', 'API\RangeController@index');
        });
        Route::group(['middleware' => ['can:show,range']], function () {
            Route::get('show/{range}', 'API\RangeController@show');
        });
        Route::group(['middleware' => ['can:publish,App\Models\Range']], function () {
            Route::post('store', 'API\RangeController@store');
        });
        Route::group(['middleware' => ['can:edit,range']], function () {
            Route::post('update/{range}', 'API\RangeController@update');
        });
        Route::group(['middleware' => ['can:delete,range']], function () {
            Route::put('restore/{range}', 'API\RangeController@restore');
            Route::delete('destroy/{range}', 'API\RangeController@destroy');
            Route::delete('forceDelete/{range}', 'API\RangeController@forceDelete');
        });
    });

    /***********************************   TASK   **************************************/
    /***********************************************************************************/
    Route::prefix('task-management')->group(function () {
        Route::group(['middleware' => ['can:read,App\Models\Task']], function () {
            Route::get('index', 'API\TaskController@index');
            Route::get('workarea/{workarea}', 'API\TaskController@getByWorkarea');
            Route::get('bundle/{task_bundle}', 'API\TaskController@getByBundle');
            Route::get('skill/{skill}', 'API\TaskController@getBySkill');
            Route::get('user/{user}', 'API\TaskController@getByUser');
            Route::post('skills', 'API\TaskController@getBySkills');
        });
        Route::group(['middleware' => ['can:show,task']], function () {
            Route::get('show/{task}', 'API\TaskController@show');
        });
        Route::group(['middleware' => ['can:publish,App\Models\Task']], function () {
            Route::post('store', 'API\TaskController@store');
        });
        Route::group(['middleware' => ['can:edit,task']], function () {
            Route::post('store-comment/{task}', 'API\TaskController@addComment');
            Route::post('update-partial/{task}', 'API\TaskController@updatePartial');
            Route::post('update/{task}', 'API\TaskController@update');
        });
        Route::group(['middleware' => ['can:delete,task']], function () {
            Route::delete('{task}', 'API\TaskController@destroy');
        });
    });

    /*****************************   REPETITIVE - TASK   ********************************/
    /***********************************************************************************/
    Route::prefix('repetitive-task-management')->group(function () {
        Route::group(['middleware' => ['can:read,App\Models\Task']], function () {
            Route::get('range/{range}', 'API\RangeController@getRepetitiveTasks');
        });
    });

    /***********************************************************************************/
    /*****************************   UNAVAILABILITIES   ********************************/
    /***********************************************************************************/
    Route::prefix('unavailability-management')->group(function () {
        Route::group(['middleware' => ['can:read,App\Models\Unavailability']], function () {
            Route::get('index', 'API\UnavailabilityController@index');
        });
        Route::group(['middleware' => ['can:show,unavailability']], function () {
            Route::get('show/{unavailability}', 'API\UnavailabilityController@show');
        });
        Route::group(['middleware' => ['can:publish,App\Models\Unavailability']], function () {
            Route::post('store', 'API\UnavailabilityController@store');
        });
        Route::group(['middleware' => ['can:edit,unavailability']], function () {
            Route::post('update/{unavailability}', 'API\UnavailabilityController@update');
        });
        Route::group(['middleware' => ['can:delete,unavailability']], function () {
            Route::delete('destroy/{unavailability}', 'API\UnavailabilityController@destroy');
        });
    });

    /***********************************************************************************/
    /***********************************   Hours   *************************************/
    /***********************************************************************************/
    Route::prefix('hours-management')->group(function () {
        Route::group(['middleware' => ['can:read,App\Models\Hours']], function () {
            Route::get('index', 'API\HoursController@index');
        });
        Route::group(['middleware' => ['can:show,hours']], function () {
            Route::get('show/{hours}', 'API\HoursController@show');
        });
        Route::group(['middleware' => ['can:publish,App\Models\Hours']], function () {
            Route::post('store', 'API\HoursController@store');
        });
        Route::group(['middleware' => ['can:edit,hours']], function () {
            Route::post('update/{hours}', 'API\HoursController@update');
        });
        Route::group(['middleware' => ['can:delete,hours']], function () {
            Route::delete('destroy/{hours}', 'API\HoursController@destroy');
        });
    });

    /***********************************************************************************/
    /***********************************   Dealing Hours   *************************************/
    /***********************************************************************************/
    Route::prefix('dealing-hours-management')->group(function () {
        Route::group(['middleware' => ['can:read,App\Models\DealingHours']], function () {
            Route::get('index', 'API\DealingHoursController@index');
            Route::get('overtimes', 'API\DealingHoursController@getOvertimes');
        });
        Route::group(['middleware' => ['can:show,dealing_hours']], function () {
            Route::get('show/{dealing_hours}', 'API\DealingHoursController@show');
        });
        Route::group(['middleware' => ['can:publish,App\Models\DealingHours']], function () {
            Route::post('store', 'API\DealingHoursController@store');
            Route::post('storeOrUpdateUsed', 'API\DealingHoursController@storeOrUpdateUsed');
        });
        Route::group(['middleware' => ['can:edit,dealing_hours']], function () {
            Route::post('update/{dealing_hours}', 'API\DealingHoursController@update');
        });
        Route::group(['middleware' => ['can:delete,dealing_hours']], function () {
            Route::delete('destroy/{dealing_hours}', 'API\DealingHoursController@destroy');
            Route::delete('forceDelete/{dealing_hours}', 'API\DealingHoursController@forceDelete');
        });
    });

    /***********************************************************************************/
    /***********************************   Customers   *************************************/
    /***********************************************************************************/
    Route::prefix('customer-management')->group(function () {
        Route::group(['middleware' => ['can:read,App\Models\Customer']], function () {
            Route::get('index', 'API\CustomerController@index');
        });
        Route::group(['middleware' => ['can:show,customer']], function () {
            Route::get('show/{customer}', 'API\CustomerController@show');
        });
        Route::group(['middleware' => ['can:publish,App\Models\Customer']], function () {
            Route::post('store', 'API\CustomerController@store');
        });
        Route::group(['middleware' => ['can:edit,customer']], function () {
            Route::post('update/{customer}', 'API\CustomerController@update');
        });
        Route::group(['middleware' => ['can:delete,customer']], function () {
            Route::put('restore/{customer}', 'API\CustomerController@restore');
            Route::delete('destroy/{customer}', 'API\CustomerController@destroy');
            Route::delete('forceDelete/{customer}', 'API\CustomerController@forceDelete');
        });
    });

    /***********************************************************************************/
    /***********************************   Modules   *************************************/
    /***********************************************************************************/
    Route::prefix('module-management')->group(function () {
        Route::get('sync/{module}', 'API\ModuleController@sync');
        Route::group(['middleware' => ['can:read,App\Models\BaseModule']], function () {
            Route::get('index', 'API\ModuleController@index');
            Route::post('test-connection', 'API\ModuleController@testConnection');
        });
        Route::group(['middleware' => ['can:show,module']], function () {
            Route::get('show/{module}', 'API\ModuleController@show');
        });
        Route::group(['middleware' => ['can:publish,App\Models\BaseModule']], function () {
            Route::post('store', 'API\ModuleController@store');
            Route::post('module-update/{id}', 'API\ModuleController@updateModule');
        });
        Route::group(['middleware' => ['can:edit,module']], function () {
            Route::post('update/{module}', 'API\ModuleController@update');
            Route::post('module-data-types-update/{module}', 'API\ModuleController@updateModuleDataTypes');
        });
        Route::group(['middleware' => ['can:delete,module']], function () {
            Route::delete('destroy/{module}', 'API\ModuleController@destroy');
        });
    });

    /***********************************************************************************/
    /***********************************   DataTypes   *************************************/
    /***********************************************************************************/
    Route::prefix('data-type-management')->group(function () {
        Route::group(['middleware' => ['can:read,App\Models\BaseModule']], function () {
            Route::get('index', 'API\DataTypeController@index');
        });
    });

    /***********************************************************************************/
    /********************************** Documents **************************************/
    /***********************************************************************************/
    Route::prefix('document-management')->group(function () {
        Route::group(['middleware' => ['can:publish tasks']], function () {
            Route::post('store', 'API\DocumentController@store');
            Route::post('upload-file/{token}', 'API\DocumentController@uploadFile');
            Route::delete('delete-file/{document}', 'API\DocumentController@deleteFile');
            Route::post('delete-files', 'API\DocumentController@deleteFiles');
        });
    });

    /***********************************************************************************/
    /********************************* Subscription ************************************/
    /***********************************************************************************/
    Route::prefix('subscription-management')->group(function () {
        Route::group(['middleware' => ['can:read,App\Models\Subscription']], function () {
            Route::get('index', 'API\SubscriptionController@index');
            Route::get('packages', 'API\SubscriptionController@packages');
            Route::get('company/{company}', 'API\SubscriptionController@getByCompany');
        });
        Route::group(['middleware' => ['can:show,subscription']], function () {
            Route::get('show/{subscription}', 'API\SubscriptionController@show');
        });
        Route::group(['middleware' => ['can:publish,App\Models\Subscription']], function () {
            Route::post('store', 'API\SubscriptionController@store');
        });
        Route::group(['middleware' => ['can:edit,subscription']], function () {
            Route::post('update/{subscription}', 'API\SubscriptionController@update');
        });
        Route::group(['middleware' => ['can:delete,subscription']], function () {
            Route::put('restore/{subscription}', 'API\SubscriptionController@restore');
            Route::delete('destroy/{subscription}', 'API\SubscriptionController@destroy');
            Route::delete('forceDelete/{subscription}', 'API\SubscriptionController@forceDelete');
        });
    });
});

/***********************************************************************************/
/***************************** NOT AUTHENTICATED ***********************************/
/***********************************************************************************/

Route::get('document-management/get-file/{path}', 'API\DocumentController@getFile');
