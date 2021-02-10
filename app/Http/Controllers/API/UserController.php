<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiException;
use App\Rules\StrongPassword;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Verified;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\User;
use App\Models\Company;
use App\Models\DealingHours;
use App\Models\ModelHasOldId;
use App\Models\Package;
use App\Models\Project;
use App\Models\Range;
use App\Models\Skill;
use App\Models\Subscription;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Unavailability;
use App\Models\WorkHours;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class UserController extends BaseApiController
{
    protected static $index_load = ['company:id,name', 'skills:id,name'];
    protected static $index_append = null;
    protected static $show_load = ['company:id,name', 'skills:id,name', 'workHours', 'unavailabilities'];
    protected static $show_append = ['related_users'];

    protected static $store_validation_array = [
        'lastname' => 'required',
        'firstname' => 'required',
        'login' => 'required',
        'company_id' => 'required',
        'role_id' => 'required',
        'skills' => 'present|array',
        'email' => 'nullable|email',
    ];

    protected static $update_validation_array = [
        'lastname' => 'required',
        'firstname' => 'required',
        'login' => 'required',
        'company_id' => 'required',
        'role_id' => 'required',
        'skills' => 'present|array',
        'email' => 'nullable|email',
        'related_user_id' => 'nullable'
    ];

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct(User::class);
    }

    protected function storeItem(array $arrayRequest)
    {
        // if ($arrayRequest['email'] && User::where('email', $arrayRequest['email'])->withTrashed()->exists()) {
        //     throw new ApiException("Émail déjà pris par un autre utilisateur, veuillez en saisir un autre.", static::$response_codes['error_conflict']);
        // }

        if (User::where('login', $arrayRequest['login'])->withTrashed()->exists()) {
            throw new ApiException("Identifiant déjà pris par un autre utilisateur, veuillez en saisir un autre.", static::$response_codes['error_conflict']);
        }

        $user = Auth::user();

        $password = Str::random(12);
        $item = User::create([
            'lastname' => $arrayRequest['lastname'],
            'firstname' => $arrayRequest['firstname'],
            'login' => $arrayRequest['login'],
            'email' => $arrayRequest['email'],
            'password' => Hash::make($password),
            'company_id' => $user->is_admin ? $arrayRequest['company_id'] : $user->company_id,
            'register_token' => Str::random(8),
            'isTermsConditionAccepted' => false,
        ]);

        //On ajoute des heures de travail par défaut ( 35H )
        $this->addDefaultWorkHours($item->id);

        $item->markEmailAsVerified();
        if ($item->email !== null) {
            $item->sendEmailAddUserNotification($item->id, $item->register_token);
        } else {
            $item->clear_password = $password;
            $item->append('clear_password');
        }

        $this->setRole($item, $arrayRequest['role_id']);

        $this->setSkills($item, $arrayRequest['skills']);

        return $item;
    }

    protected function updateItem($item, array $arrayRequest)
    {
        // if ($arrayRequest['email'] && $item->email != $arrayRequest['email'] && User::where('email', $arrayRequest['email'])->withTrashed()->exists()) {
        //     throw new ApiException("Émail déjà pris par un autre utilisateur, veuillez en saisir un autre.", static::$response_codes['error_conflict']);
        // }

        if ($item->login != $arrayRequest['login'] && User::where('login', $arrayRequest['login'])->withTrashed()->exists()) {
            throw new ApiException("Identifiant déjà pris par un autre utilisateur, veuillez en saisir un autre.", static::$response_codes['error_conflict']);
        }

        $relatedUserOldId = null;
        if (isset($arrayRequest['related_user_id'])) {
            $relatedUserOldId = ModelHasOldId::where('model', User::class)->where('new_id', $arrayRequest['related_user_id'])->first();

            if (!$relatedUserOldId) {
                throw new ApiException("L'utilisateur à relier n'existe pas.", static::$response_codes['error_conflict']);
            }
        }

        $user = Auth::user();

        $item->update([
            'lastname' => $arrayRequest['lastname'],
            'firstname' => $arrayRequest['firstname'],
            'login' => $arrayRequest['login'],
            'email' => $arrayRequest['email'],
            'company_id' => $user->is_admin ? $arrayRequest['company_id'] : $user->company_id,
        ]);

        $this->setRole($item, $arrayRequest['role_id']);

        $this->setSkills($item, $arrayRequest['skills']);

        if ($relatedUserOldId) {
            $relatedUserId = $relatedUserOldId->new_id;
            Unavailability::where('user_id', $relatedUserId)->update(['user_id' => $item->id]);
            Project::where('created_by', $relatedUserId)->update(['created_by' => $item->id]);
            Task::where('created_by', $relatedUserId)->update(['created_by' => $item->id]);
            Task::where('user_id', $relatedUserId)->update(['user_id' => $item->id]);
            TaskComment::where('created_by', $relatedUserId)->update(['created_by' => $item->id]);
            Range::where('created_by', $relatedUserId)->update(['created_by' => $item->id]);
            WorkHours::where('user_id', $relatedUserId)->delete();
            DealingHours::where('user_id', $relatedUserId)->update(['user_id' => $item->id]);

            ModelHasOldId::where('model', User::class)->where('new_id', $relatedUserOldId->new_id)->update(['new_id' => $item->id]);
            User::find($relatedUserId)->forceDelete();
        }

        $item->save();

        return $item;
    }

    /**
     * Sets the role of the user.
     *
     * @throws \App\Exceptions\ApiException
     */
    protected function setRole(User $item, int $roleId)
    {
        $role = Role::find($roleId);
        if (!$role) {
            throw new ApiException("Rôle inconnu.");
        }

        if ($user = Auth::user()) {
            if (!$user->is_admin && $role->company_id != $user->company_id && !$role->is_public) {
                throw new ApiException("Accès non authorisé.", static::$response_codes['error_unauthorized']);
            }
        } else if ($role->code == "super_admin") {
            throw new ApiException("Accès non authorisé.", static::$response_codes['error_unauthorized']);
        }

        $item->syncRoles($roleId);
    }

    /**
     * Sets the skills of the user.
     *
     * @throws \App\Exceptions\ApiException
     */
    protected function setSkills(User $item, array $skillIds)
    {
        $ids = collect($skillIds);
        foreach ($ids as $id) {
            $skill = Skill::find($id);
            if (!$skill) {
                throw new ApiException("Compétence inconnue.", static::$response_codes['error_not_found']);
            }
        }

        $item->skills()->sync($skillIds);
    }

    /**
     * Updates the account information of the user.
     */
    public function updateAccount(Request $request)
    {
        $item = Auth::user();
        if (!$item) {
            return $this->unauthorizedResponse();
        }

        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, [
            'firstname' => 'required',
            'lastname' => 'required',
            'email' => 'nullable|email'
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors());
        }

        $item->update([
            'lastname' => $arrayRequest['lastname'],
            'firstname' => $arrayRequest['firstname'],
            'email' => $arrayRequest['email'],
        ]);

        return $this->successResponse($item->load(static::$show_load), "Mise à jour terminée avec succès.");
    }

    /**
     * Updates the password of the user before the first login.
     */
    public function updatePasswordBeforeLogin(Request $request)
    {
        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, [
            'new_password' => [new StrongPassword],
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors());
        }

        $item = User::where('register_token', $arrayRequest['register_token'])->first();
        if (!$item) {
            return $this->notFoundResponse();
        }

        if ($item->is_password_change) {
            return $this->errorResponse("Le mot de passe de l'utilisateur a déjà été changé.");
        }

        $item->update([
            'password' => bcrypt($arrayRequest['new_password']),
            'is_password_change' => true,
            'register_token' => Str::random(8)
        ]);

        return $this->successResponse($item->load(static::$show_load), "Mot de passe changé avec succès.");
    }

    /**
     * Updates the password of the user.
     */
    public function updatePassword(Request $request)
    {
        $item = Auth::user();
        if (!$item) {
            return $this->unauthorizedResponse();
        }

        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, [
            'old_password' => 'required',
            'new_password' => [new StrongPassword],
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors());
        }

        if (!Hash::check($arrayRequest['old_password'], $item->password)) {
            return $this->errorResponse("L'ancien mot de passe n'est pas valide.");
        }

        $item->update([
            'password' => bcrypt($arrayRequest['new_password']),
        ]);

        return $this->successResponse($item->load(static::$show_load), "Mot de passe changé avec succès.");
    }

    /**
     * Updates the work hours of the user.
     */
    public function updateWorkHours(Request $request, int $id)
    {
        $item = User::find($id);
        if ($error = $this->itemErrors($item, 'edit')) {
            return $error;
        }

        $arrayRequest = $request->all();
        foreach ($arrayRequest['work_hours'] as $day => $hours) {
            if (!in_array(strtolower($day), WorkHours::$days)) {
                return $this->errorResponse("Le jour '{$day}' n'est pas valide.");
            }
            $validator = Validator::make($hours, [
                'is_active' => 'required',
            ]);
            if ($validator->fails()) {
                return $this->errorResponse($validator->errors());
            }
        }

        foreach ($arrayRequest['work_hours'] as $day => $hours) {
            $workHours = WorkHours::firstOrCreate(['user_id' => $item->id, 'day' => strtolower($day)]);
            $workHours->is_active = $hours['is_active'];
            $workHours->morning_starts_at = $hours['morning_starts_at'];
            $workHours->morning_ends_at = $hours['morning_ends_at'];
            $workHours->afternoon_starts_at = $hours['afternoon_starts_at'];
            $workHours->afternoon_ends_at = $hours['afternoon_ends_at'];
            $workHours->save();
        }

        return $this->successResponse($item->load(static::$show_load), 'Mise à jour terminée avec succès.');
    }

    /**
     * Sets the default work hours of the user.
     */
    private function addDefaultWorkHours(int $id)
    {
        foreach (WorkHours::$days as $day) {
            if (!in_array($day, ['samedi', 'dimanche'])) {
                WorkHours::create([
                    'user_id' => $id,
                    'is_active' => 1,
                    'day' => $day,
                    'morning_starts_at' => '09:00:00',
                    'morning_ends_at' => '12:00:00',
                    'afternoon_starts_at' =>  '13:00:00',
                    'afternoon_ends_at' => '17:00:00'
                ]);
            }
        }
    }

    /**
     * Logs the user in.
     */
    public function login(Request $request)
    {
        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, [
            'login' => 'required',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors());
        }

        if (!Auth::attempt(['login' => $arrayRequest['login'], 'password' => $arrayRequest['password']])) {
            return $this->errorResponse("Connexion impossible, l'identifiant ou le mot de passe est incorrect.");
        }

        $item = Auth::user();
        if (!$item->is_admin) {
            if (!$item->hasVerifiedEmail()) {
                Auth::logout();
                return $this->errorResponse("Connexion impossible, veuillez vérifier votre adresse e-mail avant de vous connecter.", static::$response_codes['error_request'], ["email_not_verified" => true]);
            }
            if (!$item->is_password_change) {
                Auth::logout();
                return $this->errorResponse("Connexion impossible, veuillez changer votre mot de passe avant de vous connecter.", static::$response_codes['error_request'], ["change_password" => true, "register_token" => $item->register_token]);
            }
            $company = Company::find($item->company_id);
            if (!$company) {
                Auth::logout();
                return $this->errorResponse("Connexion impossible, votre compte a été désactivé.");
            } else if (!$company->has_active_subscription) {
                Auth::logout();
                return $this->errorResponse("Connexion impossible, votre société ne dispose d'aucun abonnement actif.");
            }
            if ($company->module && $company->module->is_active) {
                $item->append('module');
            }
        }

        $token = $item->createToken('ProjetX');
        $token->token->expires_at = now()->addHours(2); // unused but prevent eventual  javascript issue
        $item->load(['company:id,name']);
        if ($item->is_manager) {
            $item->load(['company.users:id,firstname,lastname,company_id']);
        }
        $item->append('permissions');

        return $this->successResponse($item, "Connexion réussie.", ['token' => [
            'value' => $token->accessToken,
            'expires_at' => $token->token->expires_at
        ]]);
    }

    /**
     * Resend the email verification notification.
     */
    public function resendVerification(Request $request)
    {
        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors());
        }

        $item = User::where('email', $arrayRequest['email'])->first();
        if (!$item) {
            return $this->errorResponse("Émail inconnu.");
        }

        if ($item->hasVerifiedEmail()) {
            return $this->errorResponse("Émail déjà vérifié.");
        }

        try {
            $item->SendEmailVerificationNotification();
        } catch (\Throwable $th) {
            return $this->errorResponse("Impossible d'envoyer le mail de vérification.");
        }

        return $this->successResponse(true, "Émail envoyé.");
    }

    /**
     *  Mark the authenticated user's email address as verified.
     */
    public function verify(Request $request)
    {
        $item = User::find($request->route('id'));
        if ($item === null) {
            return redirect('/pages/not-authorized');
        }

        if (!hash_equals((string) $request->route('hash'), sha1($item->getEmailForVerification()))) {
            return redirect('/pages/not-authorized');
        }

        if ($item->hasVerifiedEmail()) {
            return redirect('/pages/verify/success');
            return redirect('/pages/login');
        }

        if ($item->markEmailAsVerified()) {
            event(new Verified($item));
        }
        return redirect('/pages/verify/success');
    }

    /**
     * Logs the user in via token
     */
    // public function getUserByUserToken()
    // {
    //     $item = Auth::user();
    //     if (!$item) {
    //         return $this->unauthorizedResponse();
    //     }

    //     if (!$item->is_admin) {
    //         if (!$item->hasVerifiedEmail()) {
    //             Auth::logout();
    //             return $this->errorResponse("Connexion impossible, veuillez vérifier votre adresse e-mail avant de vous connecter.", static::$response_codes['error_request'], "verify");
    //         }
    //         $company = Company::find($item->company_id);
    //         if (!$company) {
    //             Auth::logout();
    //             return $this->errorResponse("Connexion impossible, votre compte a été désactivé.");
    //         } else if (!$company->has_active_subscription) {
    //             Auth::logout();
    //             return $this->errorResponse("Connexion impossible, votre société ne dispose d'aucun abonnement actif.");
    //         }
    //         if ($company->module && $company->module->is_active) {
    //             $item->syncable = $company->module->moduleDataTypes->map(function ($mdt) {
    //                 return $mdt->dataType->slug . '-management';
    //             });
    //         }
    //     }

    //     $token = $item->createToken('ProjetX');
    //     $token->token->expires_at = now()->addHours(2); // unused but prevent eventual  javascript issue
    //     $item->token = [
    //         'value' => $token->accessToken,
    //         'expires_at' => $token->token->expires_at
    //     ];
    //     $item->load(['company:id,name']);
    //     if ($item->is_manager) {
    //         $item->load(['company.users:id,firstname,lastname,company_id']);
    //     }
    //     $item->append('permissions');

    //     return $this->successResponse($item, "Connexion réussie.");
    // }

    /**
     * Gets a user via token.
     */
    // public function getUserForRegistration($token)
    // {
    //     $item = User::where('register_token', $token)->first();
    //     if (!$item) {
    //         return $this->notFoundResponse();
    //     }

    //     return $this->successResponse(true);
    // }

    /**
     * Logs the user out.
     */
    public function logout()
    {
        if (Auth::check()) {
            Auth::user()->AauthAcessToken()->delete();
        }

        return $this->successResponse(true, "Déconnexion réussie.");
    }

    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        $arrayRequest = $request->all();
        $validator = Validator::make($arrayRequest, [
            'firstname' => 'required',
            'lastname' => 'required',
            'company_name' => 'required',
            'contact_function' => 'required',
            'email' => 'required|email',
            'contact_tel1' => 'required',
            'password' => 'required',
            'c_password' => 'required|same:password',
            'terms_accepted' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors());
        }

        if (User::where('email', $arrayRequest['email'])->withTrashed()->exists()) {
            return $this->errorResponse("Émail déjà pris par un autre utilisateur, veuillez en saisir un autre.", static::$response_codes['error_conflict']);
        }

        // creation of a temporary identifier before testing if it already exists
        $login_temp = mb_strtolower($arrayRequest['company_name'], 'UTF-8') . "." . mb_strtolower($arrayRequest['firstname'], 'UTF-8') . ucfirst(mb_strtolower($arrayRequest['lastname'], 'UTF-8'));
        $parsed_login = static::str_to_noaccent($login_temp);

        $login = $parsed_login;
        while (User::where('login', $login)->withTrashed()->exists()) {
            $login = $parsed_login . rand(0, 9999);
        }

        DB::beginTransaction();
        $item = User::create([
            'lastname' => $arrayRequest['lastname'],
            'firstname' => $arrayRequest['firstname'],
            'login' => $login,
            'email' => $arrayRequest['email'],
            'password' => bcrypt($arrayRequest['password']),
            'is_password_change' => true,
            'isTermsConditionAccepted' => $arrayRequest['terms_accepted'],
        ]);

        try {
            $role = Role::where('code', 'admin')->firstOrFail();
            $this->setRole($item, $role->id);
        } catch (ApiException $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage(), $th->getHttpCode());
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
            return $this->errorResponse("Erreur serveur.", static::$response_codes['error_server']);
        }

        $company = Company::create([
            'name' => $arrayRequest['company_name'],
            'contact_firstname' => $arrayRequest['firstname'],
            'contact_lastname' => $arrayRequest['lastname'],
            'contact_function' => $arrayRequest['contact_function'],
            'contact_email' => $arrayRequest['email'],
            'contact_tel1' => $arrayRequest['contact_tel1'],
        ]);
        $item->company()->associate($company);
        $item->save();

        $subscription = Subscription::create([
            'starts_at' => Carbon::now(),
            'ends_at' => Carbon::now()->addMonth(),
            'is_trial' => true,
            'state' => 'active',
            'company_id' => $company->id
        ]);
        $subscription->packages()->sync(Package::pluck('id'));

        DB::commit();

        //$item->sendEmailVerificationNotification();

        $token = $item->createToken('ProjetX');
        $token->token->expires_at = now()->addHours(2); // unused but prevent eventual  javascript issue
        $item->load(['company:id,name']);
        if ($item->is_manager) {
            $item->load(['company.users:id,firstname,lastname,company_id']);
        }
        $item->append('permissions');

        return $this->successResponse($item, "Inscription réussie.", ['token' => [
            'value' => $token->accessToken,
            'expires_at' => $token->token->expires_at
        ]]);
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
}
