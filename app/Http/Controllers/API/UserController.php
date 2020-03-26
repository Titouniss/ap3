<?php
namespace App\Http\Controllers\API;
use Illuminate\Http\Request; 
use App\Http\Controllers\Controller; 
use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Carbon;
use Validator;

use App\User; 
use App\Models\Company;
use Spatie\Permission\Models\Role;

class UserController extends Controller 
{
    public $successStatus = 200;
    /** 
     * login api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function login(){ 
        if(Auth::attempt(['email' => request('email'), 'password' => request('password')])){ 
            $user = Auth::user(); 
            if (!$user->hasVerifiedEmail()) {
                return response()->json(['success' => false, 'verify' => false], $this-> successStatus); 
            }
            $token =  $user->createToken('ProjetX');
            $success['token'] =  $token->accessToken;
            $success['tokenExpires'] =  $token->token->expires_at;
            $user->load(['roles' => function ($query) {
                $query->select(['id','name'])->with(['permissions' => function ($query) {
                    $query->select(['id','name','name_fr', 'isPublic']);
                }]);
            }])->load('company:id,name');
            return response()->json(['success' => $success, 'userData' => $user], $this-> successStatus); 
        } 
        else{ 
            return response()->json(['success' => false, 'error'=>'Unauthorised']); 
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

        if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
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
    public function getUserByToken () { 
        $user = Auth::user();
        $success = false;
        if ($user != null) {
            $user->load(['roles' => function ($query) {
                $query->select(['id','name'])->with(['permissions' => function ($query) {
                    $query->select(['id','name','name_fr', 'isPublic']);
                }]);
            }])->load('company:id,name');
            $success = true;
        }
        response()->json(['success' => $success, 'userData' => $user], $this-> successStatus); 
    }

    /** 
    * logout api 
    * 
    * @return \Illuminate\Http\Response 
    */ 
   public function logout(){ 
    $success = false;
    if (Auth::check()) {
        Auth::user()->AauthAcessToken()->delete();
        $success = true;
     }
     return response()->json(['success'=>$success], $this-> successStatus); 
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
            return response()->json(['error'=>$validator->errors()], 401);            
        }
        $input = $request->all(); 
        $input['password'] = bcrypt($input['password']); 
        $user = User::create($input);
        if ($user == null) {
            return response()->json(['error'=>$validator->errors()], 401);
        }
        $role = Role::where('name', 'clientAdmin');
        if ($role != null) {
            $user->assignRole('clientAdmin'); // pour les nouveaux inscrits
        }
        $company = Company::create(['name' => 'Entreprise '.$user->lastname, 'expire_at' => now()->addDays(29)]);
        $user->company()->associate($company);
        $user->save();
        $user->sendEmailVerificationNotification();
        $token =  $user->createToken('ProjetX');
        $success['token'] =  $token->accessToken;
        $success['tokenExpires'] =  $token->token->expires_at;
        return response()->json(['success'=>$success, 'userData' => $user, 'company' => $company], $this-> successStatus); 
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
            $usersList = User::withTrashed()->get()->load('roles');
        } else if ($user->company_id != null) {
            $usersList = User::where('company_id',$user->company_id)->get()->load('roles:id,name','company:id,name');
        }
        return response()->json(['success' => $usersList], $this-> successStatus); 
    } 

      
    /** 
     * get single item api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function show($id) 
    { 
        $item = User::where('id',$id)->first()->load('roles:id,name','company:id,name');
        return response()->json(['success' => $item], $this-> successStatus); 
    } 

    /** 
     * update item api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function update(Request $request, $id) 
    { 
        $arrayRequest = $request->all();
        
        $validator = Validator::make($arrayRequest, [ 
            'firstname' => 'required',
            'lastname' => 'required'
            ]);
        $user = User::withTrashed()->find($id);
        if ($user != null) {
            if (isset($arrayRequest['roles'])) {
                $roles = array();
                foreach ($arrayRequest['roles'] as $role) {
                    array_push($roles,$role['id']);
                }
                $user->syncRoles($roles);
            }
            $user->firstname = $arrayRequest['firstname'];
            $user->lastname = $arrayRequest['lastname'];
            $user->save();
        }
        return response()->json(['success' => $user], $this-> successStatus); 
    } 

    /** 
     * delete item api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function destroy($id) 
    { 
        $item = User::where('id',$id)->delete();
        return response()->json(['success' => $item], $this-> successStatus); 
    } 
}