<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\Models\Customer;
use App\Http\Requests\SignUpRequest;
use App\Http\Requests\SocialSignUpReruest;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['login', 'register','signup','social_signup']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request){
    	$validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (! $token = auth()->attempt($validator->validated())) {
            return response()->json(['error' => 'Email or password does not exsist'], 401);
        }

        return $this->createNewToken($token);
    }


    public function signup(SignUpRequest $request)
    {
        Customer::create($request->all());
        return $this->login($request);
    }

    public function social_signup(SocialSignUpReruest $request)
    {
        Customer::create($request->all());
        return $this->login($request);
    }    
    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:customer',
            'password' => 'required|string|confirmed|min:6',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = Customer::create(array_merge(
                    $validator->validated(),
                    ['password' => bcrypt($request->password)]
                ));

        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user
        ], 201);
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

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()
        ]);
    }





    public function social_signup_all(Request $request)
    {
        $payLoad = json_decode($request->getContent(), true);

        $validator = Validator::make($payLoad, [
            'name' => 'required',
            'email' => 'required|string|email|max:100|unique:customer',
        ]);

        if($validator->validated()){
            DB::table('customer')->insert([
                'email'        =>$payLoad['email'],
                'password'     =>bcrypt($payLoad['provider_id']),
                'customer_name'=>$payLoad['name'],
                'provider'     =>$payLoad['provider'],
                'provider_id'  =>$payLoad['provider_id'],
                'provider_pic' =>$payLoad['provider_pic'],
                'token'        =>$payLoad['token']
            ]);
    
            $userData=$this->internalUserDetails($payLoad['email']);
    
            return $userData;
        }
    }

    private function internalUserDetails($email){
        $data= DB::table('customer')
                    ->where('customer.email', $email)
                    ->select('customer.id','customer.email','customer.customer_name','customer.provider','customer.provider_id','customer.provider_pic','customer.token')                        
                    ->first();
        $data =array(
            'name'=>$data->customer_name,
            'email'=>$data->email,
            'provider'=>$data->provider,
            'token'=>$data->token,
            'id'=>$data->id,
            'image'=>$data->provider_pic,
        );   
        
        $userData =array(
            'userData'=>$data
        );

        return response()->json($userData);  
                  

    }

}
