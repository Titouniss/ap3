<?php
namespace App\Http\Controllers\API;
use Illuminate\Http\Request; 
use App\Http\Controllers\Controller; 
use App\User; 
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth; 
use Validator;
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
            $token =  $user->createToken('ProjetX');
            $success['token'] =  $token->accessToken;
            $success['tokenExpires'] =  $token->token->expires_at;
            return response()->json(['success' => $success, 'userData' => $user], $this-> successStatus); 
        } 
        else{ 
            return response()->json(['success' => false, 'error'=>'Unauthorised']); 
        } 
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
        $token =  $user->createToken('ProjetX');
        $success['token'] =  $token->accessToken;
        $success['tokenExpires'] =  $token->token->expires_at;
        return response()->json(['success'=>$success, 'userData' => $user], $this-> successStatus); 
    }
    /** 
     * details api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function details() 
    { 
        $user = Auth::user(); 
        return response()->json(['success' => $user], $this-> successStatus); 
    } 

        /** 
     * details api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function index() 
    { 
        $user = Auth::user();
        $usersList = [];
        if ($user->hasRole('superAdmin')) {
            $usersList = User::all();
        } else if ($user->company_id != null) {
            $usersList = User::where('company_id',$user->company_id)->get();
        }
        return response()->json(['success' => $usersList], $this-> successStatus); 
    } 
}