<?php

namespace App\Http\Controllers\v2;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Validator;
use JWTAuth;
use Carbon\Carbon;

use App\Models\Customer;
use App\Models\PasswordReset;

use App\Http\Requests\SignUpRequest;
use App\Http\Requests\SocialSignUpReruest;


class AuthController extends Controller
{

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['login', 'signup','socialSignup']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request){

        try {

            $validation_array =[
                'email'         => 'required|string|email|max:100',
                'password'      => 'required|string|min:4',
                'franchise'     => 'required',
            ];
    
            $validator = Validator::make($request->all(), $validation_array);
    
            if($validator->fails()){
                return response()->json(['success' => false,'data'=> null,'message' => implode(" / ",$validator->messages()->all())], 422);
            }
    
            if (! $token = JWTAuth::claims(['franchise' => $request->franchise])->attempt($validator->validated())) {
                return response()->json(['success' => false,'data'=> null,'message' => 'Email or password does not exsist'], 404);   
            }
    
            return $this->createNewToken($token);

        } catch (\Exception $e) {
        
            return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }

    }


    public function signup(Request $request)
    {
        try {

            $validation_array =[
                'customer_name' => 'required|string|between:2,100',
                'email'         => 'required|string|email|max:100|unique:customer,email,NULL,id,franchise,'.request('franchise'),
                'password'      => 'required|string|confirmed|min:4',
                'franchise'     => 'required',
            ];
    
            $validator = Validator::make($request->all(), $validation_array);
    
            if($validator->fails()){
                return response(['success' => false,'data'=> null,'message' => implode(" / ",$validator->messages()->all())], 200);   
            }
    
            Customer::create($request->all());
            
            return $this->login($request);

        } catch (\Exception $e) {
        
            return response(['success' => false,'data'=> null,'message' => "Opps!. Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }
        
        
    }

    public function social_signup(SocialSignUpReruest $request)
    {
        Customer::create($request->all());
        return $this->login($request);
    }    


    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout() {
        auth()->logout();

        return response()->json(['message' => 'User successfully signed out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh() {
        return $this->createNewToken(auth()->refresh());
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile() {
        return response()->json(auth()->user());
    }


    public function socialSignup(Request $request)
    {

        $validation_array =[
            'customer_name' => 'required',
            'email'         => 'required|string|email|max:100',
            'password'      => 'required',
            'provider'      => 'required',
            'provider_pic'  => 'required',
        ];

        $validator = Validator::make($request->all(), $validation_array);

        if($validator->fails()){
            return response(['success' => false,'data'=> null,'message' => implode(" / ",$validator->messages()->all())], 200);   
        }

        $customer =   Customer::where(['email' => $request->email])->first();

        if($customer){
            return $this->login($request);
        }else{

            Customer::create($request->all());
            return $this->login($request);
        }

    }


    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token){

        $data =array(
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth()->factory()->getTTL() * 60,
            'user'         => auth()->user()
        );

        return response(['success' => true,'data'=>  $data,'message' => 'Login success'], 200);  

    }



}
