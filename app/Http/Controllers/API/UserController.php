<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Notifications\UserRegistration;
use App\Rules\StrongPassword;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Verified;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


use Validator;
use Mail;

use App\User;
use App\Models\Company;
use App\Models\WorkHours;
use App\Models\UsersSkill;
use Spatie\Permission\Models\Role;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class UserController extends Controller
{
    public $successStatus = 200;
    /**
     * login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login()
    {
        if (Auth::attempt(['email' => request('email'), 'password' => request('password')])) {
            $user = Auth::user();
            if (!$user->hasVerifiedEmail()) {
                return response()->json(['success' => false, 'verify' => false], $this->successStatus);
            }
            $token =  $user->createToken('ProjetX');
            $success['token'] =  $token->accessToken;
            $success['tokenExpires'] =  $token->token->expires_at;
            $user->load(['roles' => function ($query) {
                $query->select(['id', 'name'])->with(['permissions' => function ($query) {
                    $query->select(['id', 'name', 'name_fr', 'isPublic']);
                }]);
            }])->load('company:id,name');
            return response()->json(['success' => $success, 'userData' => $user], $this->successStatus);
        } else {
            return response()->json(['success' => false, 'error' => 'Unauthorised']);
        }
    }

    /**
     * Resend the email verification notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function resendVerification(Request $request)
    {
        $verificationSend = false;
        $arrayRequest = $request->all();

        if (isset($arrayRequest['email'])) {
            $user = User::where('email', $arrayRequest['email'])->first();

            if (!$user->hasVerifiedEmail()) {
                $user->SendEmailVerificationNotification();
                $verificationSend = true;
            }
        }
        return response()->json(['success' => $verificationSend], 200);
    }

    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function verify(Request $request)
    {
        $user = User::find($request->route('id'));
        if ($user === null) {
            return redirect('/pages/not-authorized');
        }

        if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            return redirect('/pages/not-authorized');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect('/pages/verify/success');
            return redirect('/pages/login');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }
        return redirect('/pages/verify/success');
    }
    /**
     * login api
     *
     * @return \Illuminate\Http\Response
     */
    public function getUserByUserToken()
    {
        $user = Auth::user();
        $success = false;
        if ($user != null) {
            $user->load(['roles' => function ($query) {
                $query->select(['id', 'name'])->with(['permissions' => function ($query) {
                    $query->select(['id', 'name', 'name_fr', 'isPublic']);
                }]);
            }])->load('company:id,name');
            $success = true;
        }
        response()->json(['success' => $success, 'userData' => $user], $this->successStatus);
    }

    /**
     * get single item api
     *
     * @return \Illuminate\Http\Response
     */
    public function getUserForRegistration($token)
    {
        $item = User::where('register_token', $token)->first();
        $success = isset($item) ? true : false;
        return response()->json(['success' => $success, 'userData' => $item], $success ? $this->successStatus : 404);
    }

    /**
     * logout api
     *
     * @return \Illuminate\Http\Response
     */
    public function logout()
    {
        $success = false;
        if (Auth::check()) {
            Auth::user()->AauthAcessToken()->delete();
            $success = true;
        }
        return response()->json(['success' => $success], $this->successStatus);
    }

    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => 'required',
            'lastname' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password',
            'isTermsConditionAccepted' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        if ($user == null) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $role = Role::where('name', 'Utilisateur');
        if ($role != null) {
            $user->assignRole('Utilisateur'); // pour les nouveaux inscrits on leur donne tout les droits d'entreprise
        }
        $company = Company::where(['siret' => 'test_users'])->first();
        $user->company()->associate($company);
        $user->save();
        $user->sendEmailVerificationNotification();
        $token =  $user->createToken('ProjetX');
        $success['token'] =  $token->accessToken;
        $success['tokenExpires'] =  $token->token->expires_at;
        return response()->json(['success' => $success, 'userData' => $user, 'company' => $company], $this->successStatus);
    }

    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function registerWithToken(Request $request, $token)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => 'required',
            'lastname' => 'required',
            'password' => 'required',
            'c_password' => 'required|same:password',
            'isTermsConditionAccepted' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $input = $request->all();
        $user = User::where('register_token', $token)->where('email', $input['email'])->first();
        if (!isset($user)) return response()->json(['success' => false], 404);
        // get full data user before or after update
        $user->load(['roles' => function ($query) {
            $query->select(['id', 'name'])->with(['permissions' => function ($query) {
                $query->select(['id', 'name', 'name_fr', 'isPublic']);
            }]);
        }])->load('company:id,name');

        // update user data
        $user->password = bcrypt($input['password']);
        $user->firstname = $input['firstname'];
        $user->lastname = $input['lastname'];
        $user->isTermsConditionAccepted = $input['isTermsConditionAccepted'];
        $user->register_token = null;
        $user->markEmailAsVerified();
        $user->save();

        // generate access token
        $token =  $user->createToken('ProjetX');
        $success['token'] =  $token->accessToken;
        $success['tokenExpires'] =  $token->token->expires_at;

        return response()->json(['success' => $success, 'userData' => $user], $this->successStatus);
    }

    /**
     * get all items api
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        $usersList = [];
        if ($user->hasRole('superAdmin')) {
            $usersList = User::withTrashed()->get()->load('roles', 'company:id,name', 'skills');
        } else if ($user->company_id != null) {
            $usersList = User::where('company_id', $user->company_id)->get()->load('roles:id,name', 'company:id,name', 'skills');
        }
        return response()->json(['success' => $usersList], $this->successStatus);
    }


    /**
     * add item api
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, [
            'lastname' => 'required',
            'firstname' => 'required',
            'email' => 'required',
            'company_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $arrayRequest['password'] = Hash::make(Str::random(12)); // on créer un password temporaire
        $arrayRequest['register_token'] = Str::random(8); // on génère un token qui représentera le lien d'inscription
        $arrayRequest['isTermsConditionAccepted'] = false;
        $item = User::create($arrayRequest)->load('company');
        if (isset($arrayRequest['roles'])) {
            $item->assignRole($arrayRequest['roles']); // on ajoute le role à l'utilisateur
        } else {
            // on assigne le rôle d'utilisateur sans droit par défault pour éviter un bug.
            $item->assignRole('basicUsers');
        }
        if (!empty($arrayRequest['skills'])) {
            foreach ($arrayRequest['skills'] as $skill_id) {
                UsersSkill::create(['user_id' => $item->id, 'skill_id' => $skill_id]);
            }
        }

        //$item->notify(new UserRegistration($item));

        return response()->json(['success' => $arrayRequest], $this->successStatus);
        //Il faut envoyer un email avec lien d'inscription

    }

    /**
     * get single item api
     *
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $item = User::where('id', $user->id)
            ->with('roles:id,name', 'company:id,name', 'workHours', 'unavailabilities', 'skills')
            ->first();
        return response()->json(['success' => $item], isset($item) ? $this->successStatus : 404);
    }

    /**
     * update item api
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $arrayRequest = $request->all();

        $validator = Validator::make($arrayRequest, [
            'firstname' => 'required',
            'lastname' => 'required',
            'email' => 'required',
            'company_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        if ($user != null) {
            if (isset($arrayRequest['roles']) || $arrayRequest['roles'] !== null) {
                $roles = array();
                foreach ($arrayRequest['roles'] as $role) {
                    array_push($roles, $role['id']);
                }
                $user->syncRoles($roles);
            }

            $user->firstname = $arrayRequest['firstname'];
            $user->lastname = $arrayRequest['lastname'];
            $user->email = $arrayRequest['email'];
            $user->company_id = $arrayRequest['company_id'];

            UsersSkill::where('user_id', $user->id)->delete();
            if (!empty($arrayRequest['skills'])) {
                foreach ($arrayRequest['skills'] as $skill_id) {
                    UsersSkill::create(['user_id' => $user->id, 'skill_id' => $skill_id]);
                }
            }

            $user->save();
        }
        return response()->json(['success' => $user], $this->successStatus);
    }

    /**
     * update account item api
     *
     * @return \Illuminate\Http\Response
     */
    public function updateAccount(Request $request, User $user)
    {
        $arrayRequest = $request->all();

        $validator = Validator::make($arrayRequest, [
            'firstname' => 'required',
            'lastname' => 'required',
            'email' => 'email'
        ]);

        if ($user != null) {
            $user->firstname = $arrayRequest['firstname'];
            $user->lastname = $arrayRequest['lastname'];
            $user->email = $arrayRequest['email'];
            $user->save();
        }
        return response()->json(['success' => $user], $this->successStatus);
    }

    /**
     * update password api
     *
     * @return \Illuminate\Http\Response
     */
    public function updatePassword(Request $request, User $user)
    {
        $rule = ['password' => [new StrongPassword]];

        $arrayRequest = $request->all();

        // Verify user exist
        if ($user != null) {
            // Verify old same password
            if (Hash::check($arrayRequest['old_password'], $user->password)) {
                // Verify password format
                if (Validator::Make(['password' => $arrayRequest['new_password']], $rule)->passes()) {
                    // Save password
                    $user->password = bcrypt($arrayRequest['new_password']);
                    $user->save();
                    return response()->json('success');
                } else {
                    Log::debug('ICI 3 :');
                    return response()->json('error_format', 400);
                }
            } else {
                return response()->json('error_old_password', 400);
            }
        }
    }

    /**
     * update work hours item api
     *
     * @return \Illuminate\Http\Response
     */
    public function updateWorkHours(Request $request, User $user)
    {
        $arrayRequest = $request->all();

        foreach ($arrayRequest['work_hours'] as $day => $hours) {
            if (!in_array(strtolower($day), WorkHours::$days)) {
                return response()->json(['error' => 'Invalid index'], 401);
            }
            $validator = Validator::make($hours, [
                'is_active' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 401);
            }
        }

        if ($user != null) {
            foreach ($arrayRequest['work_hours'] as $day => $hours) {
                $workHours = WorkHours::firstOrCreate(['user_id' => $user->id, 'day' => strtolower($day)]);
                $workHours->is_active = $hours['is_active'];
                $workHours->morning_starts_at = $hours['morning_starts_at'];
                $workHours->morning_ends_at = $hours['morning_ends_at'];
                $workHours->afternoon_starts_at = $hours['afternoon_starts_at'];
                $workHours->afternoon_ends_at = $hours['afternoon_ends_at'];
                $workHours->save();
            }
        }
        return response()->json(['success' => User::where('id', $user->id)->with('workHours', 'unavailabilities')->first()], $this->successStatus);
    }

    /**
     * delete item api
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $item = User::where('id', $id)->delete();
        $controllerLog = new Logger('user');
        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
        $controllerLog->info('user', ['response', $item]);
        return response()->json(['success' => $item], $this->successStatus);
    }
}
