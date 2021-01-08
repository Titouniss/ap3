<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Notifications\UserRegistration;
use App\Notifications\MailAddUserNotification;
use App\Rules\StrongPassword;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Verified;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use Validator;

use App\User;
use App\Models\Company;
use App\Models\DealingHours;
use App\Models\ModelHasOldId;
use App\Models\Project;
use App\Models\Range;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Unavailability;
use App\Models\WorkHours;
use App\Models\UsersSkill;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserController extends Controller
{
    use SoftDeletes;
    public $successStatus = 200;

    /**
     * check username and password api
     *
     * @return \Illuminate\Http\Response
     */
    public function checkUsernamePwdBeforeLogin(Request $request)
    {
        $arrayRequest = $request->all();
        $user = User::where('login', $arrayRequest['login'])->first();

        if (Auth::attempt(['login' => $arrayRequest['login'], 'password' => $arrayRequest['password']])) {
            Auth::logout();
            return response()->json(['success' => true, 'userData' => $user], $this->successStatus);
        } else {
            return response()->json(['success' => false, 'error' => 'Unauthorised'], $this->successStatus);
        }
    }

    /**
     * login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login()
    {
        if (Auth::attempt(['login' => request('login'), 'password' => request('password')])) {
            $module = null;
            $user = Auth::user();

            if (!$user->role->is_admin) {
                $company = Company::find($user->company_id);
                if (!$company) {
                    Auth::logout();
                    return response()->json(['success' => false, 'error' => 'Connexion impossible, votre compte a été désactivé'], 400);
                } else if (!$company->has_active_subscription) {
                    Auth::logout();
                    return response()->json(['success' => false, 'error' => 'Connexion impossible, votre société ne dispose d\'aucun abonnement actif'], 400);
                }
                if ($company->module && $company->module->is_active) {
                    $module = $company->module->load('moduleDataTypes', 'moduleDataTypes.dataType');
                }
            }

            if (!$user->hasVerifiedEmail()) {
                Auth::logout();
                return response()->json(['success' => false, 'error' => 'Veuillez valider votre adresse e-mail avant de vous connecter', 'verify' => false], 400);
            }

            $token = $user->createToken('ProjetX');
            $token->token->expires_at = now()->addHours(2); // unused but prevent eventual  javascript issue
            $success['token'] =  $token->accessToken;
            $success['tokenExpires'] =  $token->token->expires_at;
            $user->load(['roles' => function ($query) {
                $query->select(['id', 'name'])->with(['permissions' => function ($query) {
                    $query->select(['id', 'name', 'name_fr', 'is_public']);
                }]);
            }]);
            $user->load(['company:id,name'])->append('permissions');
            if ($user->hasRole('Administrateur')) {
                $user->load(['company.users:id,firstname,lastname,company_id']);
            }

            return response()->json(['success' => $success, 'userData' => $user, 'module' => ($module && $module->count() > 0 ? $module : null)], $this->successStatus);
        } else {
            return response()->json(['success' => false, 'error' => 'Connexion impossible l\'identifiant ou le mot de passe est incorrect'], 400);
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
                    $query->select(['id', 'name', 'name_fr', 'is_public']);
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
        $input = $request->all();
        $validator = Validator::make($input, [
            'firstname' => 'required',
            'lastname' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password',
            'company_name' => 'required',
            'isTermsConditionAccepted' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        if (User::where('email', $input['email'])->withTrashed()->exists()) {
            return response()->json(['error' => 'Émail déjà pris par un autre utilisateur, veuillez en saisir un autre'], 409);
        }

        // creation of a temporary identifier before testing if it already exists
        $login_temp = mb_strtolower($input['company_name'], 'UTF-8') . "." . mb_strtolower($input['firstname'], 'UTF-8') . ucfirst(mb_strtolower($input['lastname'], 'UTF-8'));
        $parsed_login = UserController::str_to_noaccent($login_temp);

        $login = $parsed_login;

        do {
            $login = $parsed_login;
            $login = $parsed_login . rand(0, 9999);
        } while (User::where('login', $login)->withTrashed()->exists());

        $input['login'] = $login;

        $input['password'] = bcrypt($input['password']);
        $input['is_password_change'] = 1;

        $controllerLog = new Logger('user');
        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
        $controllerLog->info('input', [$input]);

        $user = User::create($input);
        if ($user == null) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $role = Role::where('name', 'Administrateur');
        if ($role != null) {
            $user->assignRole('Administrateur'); // pour les nouveaux inscrits on leur donne tout les droits d'entreprise
        }
        $company = Company::create(['name' => $input['company_name'], 'is_trial' => true, 'expires_at' => (new Carbon())->addWeeks(4)]);
        $user->company()->associate($company);
        $user->save();
        $user->sendEmailVerificationNotification();
        $token =  $user->createToken('ProjetX');
        $token->token->expires_at = now()->addHours(2);  // unused but prevent eventual  javascript issue
        $success['token'] =  $token->accessToken;
        $success['tokenExpires'] =  $token->token->expires_at;
        return response()->json(['success' => $success, 'userData' => $user, 'company' => $company], $this->successStatus);
    }

    /**
     * Replace special character
     *
     * @return \Illuminate\Http\Response
     */
    public static function str_to_noaccent($str)
    {
        $parsed = $str;
        $parsed = preg_replace('#Ç#', 'C', $parsed);
        $parsed = preg_replace('#ç#', 'c', $parsed);
        $parsed = preg_replace('#è|é|ê|ë#', 'e', $parsed);
        $parsed = preg_replace('#È|É|Ê|Ë#', 'E', $parsed);
        $parsed = preg_replace('#à|á|â|ã|ä|å#', 'a', $parsed);
        $parsed = preg_replace('#@|À|Á|Â|Ã|Ä|Å#', 'A', $parsed);
        $parsed = preg_replace('#ì|í|î|ï#', 'i', $parsed);
        $parsed = preg_replace('#Ì|Í|Î|Ï#', 'I', $parsed);
        $parsed = preg_replace('#ð|ò|ó|ô|õ|ö#', 'o', $parsed);
        $parsed = preg_replace('#Ò|Ó|Ô|Õ|Ö#', 'O', $parsed);
        $parsed = preg_replace('#ù|ú|û|ü#', 'u', $parsed);
        $parsed = preg_replace('#Ù|Ú|Û|Ü#', 'U', $parsed);
        $parsed = preg_replace('#ý|ÿ#', 'y', $parsed);
        $parsed = preg_replace('#Ý#', 'Y', $parsed);

        return ($parsed);
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
                $query->select(['id', 'name', 'name_fr', 'is_public']);
            }]);
        }])->load('company:id,name');

        // update user data
        $user->password = bcrypt($input['password']);
        $user->firstname = $input['firstname'];
        $user->lastname = $input['lastname'];
        $user->isTermsConditionAccepted = $input['isTermsConditionAccepted'];
        $user->register_token = null;
        $user->save();

        // generate access token
        $token =  $user->createToken('ProjetX');
        $token->token->expires_at = now()->addHours(2);  // unused but prevent eventual  javascript issue
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
        if ($user->is_admin) {
            $usersList = User::withTrashed()->get()->load('roles', 'company:id,name', 'skills');
        } else if ($user->company_id != null) {
            $usersList = User::withTrashed()->where('company_id', $user->company_id)->get()->load('roles:id,name', 'company:id,name', 'skills');
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
            'company_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        if ($arrayRequest['email'] && User::where('email', $arrayRequest['email'])->withTrashed()->exists()) {
            return response()->json(['error' => 'Émail déjà pris par un autre utilisateur, veuillez en saisir un autre'], 409);
        }

        if (User::where('login', $arrayRequest['full_login'])->withTrashed()->exists()) {
            return response()->json(['error' => 'Identifiant déjà pris par un autre utilisateur, veuillez en saisir un autre'], 409);
        }
        $password = Str::random(12);
        $password_not_hash = $password;
        $arrayRequest['password'] = Hash::make($password); // on créer un password temporaire
        $arrayRequest['register_token'] = Str::random(8); // on génère un token qui représentera le lien d'inscription
        $arrayRequest['isTermsConditionAccepted'] = false;
        $arrayRequest['login'] = $arrayRequest['full_login'];
        $item = User::create($arrayRequest)->load('company');
        $item->markEmailAsVerified();
        if ($item->email !== null) {
            $item->sendEmailAdUserNotification($item->id, $item->register_token);
        }

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

        return response()->json(['success' => [$item, $password_not_hash]], $this->successStatus);
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
            ->first()->append('related_users');
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
            'company_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        if ($user->email != $arrayRequest['email'] && User::where('email', $arrayRequest['email'])->withTrashed()->exists()) {
            return response()->json(['error' => 'Émail déjà pris par un autre utilisateur, veuillez en saisir un autre'], 409);
        }

        if ($user->login != $arrayRequest['full_login'] && User::where('login', $arrayRequest['full_login'])->withTrashed()->exists()) {
            return response()->json(['error' => 'Identifiant déjà pris par un autre utilisateur, veuillez en saisir un autre'], 409);
        }

        $relatedUserOldId = null;
        if (in_array('related_user_id', array_keys($arrayRequest))) {
            if ($arrayRequest['related_user_id']) {
                $relatedUserOldId = ModelHasOldId::where('model', User::class)->where('new_id', $arrayRequest['related_user_id'])->first();

                if (!$relatedUserOldId) {
                    return response()->json(['error' => 'L\'utilisateur à relier n\'existe pas'], 409);
                }
            }
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
            $user->login = $arrayRequest['full_login'];
            $user->email = $arrayRequest['email'];
            $user->company_id = $arrayRequest['company_id'];

            UsersSkill::where('user_id', $user->id)->delete();
            if (!empty($arrayRequest['skills'])) {
                foreach ($arrayRequest['skills'] as $skill_id) {
                    UsersSkill::create(['user_id' => $user->id, 'skill_id' => $skill_id]);
                }
            }

            if ($relatedUserOldId) {
                $relatedUserId = $relatedUserOldId->new_id;
                Unavailability::where('user_id', $relatedUserId)->update(['user_id' => $user->id]);
                Project::where('created_by', $relatedUserId)->update(['created_by' => $user->id]);
                Task::where('created_by', $relatedUserId)->update(['created_by' => $user->id]);
                Task::where('user_id', $relatedUserId)->update(['user_id' => $user->id]);
                TaskComment::where('created_by', $relatedUserId)->update(['created_by' => $user->id]);
                Range::where('created_by', $relatedUserId)->update(['created_by' => $user->id]);
                WorkHours::where('user_id', $relatedUserId)->delete();
                DealingHours::where('user_id', $relatedUserId)->update(['user_id' => $user->id]);

                ModelHasOldId::where('model', User::class)->where('new_id', $relatedUserOldId->new_id)->update(['new_id' => $user->id]);
                User::find($relatedUserId)->forceDelete();
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
     * update password before login api
     *
     * @return \Illuminate\Http\Response
     */
    public function updatePasswordBeforeLogin(Request $request)
    {
        $arrayRequest = $request->all();

        $controllerLog = new Logger('user');
        $controllerLog->pushHandler(new StreamHandler(storage_path('logs/debug.log')), Logger::INFO);
        $controllerLog->info('arrayRequest', [$arrayRequest]);

        $rule = ['password' => [new StrongPassword]];
        $user = User::where('register_token', $arrayRequest['register_token'])->first();

        // Verify user exist
        if ($user != null) {
            if (Validator::Make(['password' => $arrayRequest['new_password']], $rule)->passes()) {
                // Save password
                $user->password = bcrypt($arrayRequest['new_password']);
                $user->is_password_change = 1;
                $user->register_token = Str::random(8);
                $user->save();

                return response()->json(['success' => true], $this->successStatus);
            } else {
                return response()->json('error_format', 400);
            }
        }
    }

    /**
     * update password api
     *
     * @return \Illuminate\Http\Response
     */
    public function updatePassword(Request $request, User $user)
    {
        $arrayRequest = $request->all();
        $rule = ['password' => [new StrongPassword]];

        // Verify user exist
        if ($user != null) {
            // Verify old same password
            if (Hash::check($arrayRequest['old_password'], auth()->user()->password)) {
                // Verify password format
                if (Validator::Make(['password' => $arrayRequest['new_password']], $rule)->passes()) {
                    // Save password
                    $user->password = bcrypt($arrayRequest['new_password']);
                    $user->save();

                    return response()->json(['success' => $user], $this->successStatus);
                } else {
                    Log::debug('ICI 3 :');
                    return response()->json('error_format', 400);
                }
            } else {
                return response()->json('error_old_password', 400);
            }
        } else {
            return response()->json('error_user', 400);
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
     * Restore the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        $item = User::withTrashed()->findOrFail($id)->restore();
        if ($item) {
            $item = User::where('id', $id)->first();
            return response()->json(['success' => $item], $this->successStatus);
        }
    }

    /**
     * delete item api
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $item = User::findOrFail($id);
        Task::where('user_id', $id)->update(['user_id' => null]);
        $item->delete();
        return response()->json(['success' => $item], $this->successStatus);
    }

    /**
     * forceDelete the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function forceDelete($id)
    {
        $item = User::withTrashed()->findOrFail(intval($id));

        $item->forceDelete();
        return response()->json(['success' => true], $this->successStatus);
    }
}
