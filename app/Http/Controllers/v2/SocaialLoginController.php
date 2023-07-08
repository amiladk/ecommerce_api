<?php

namespace App\Http\Controllers\v2;
use App\Http\Controllers\Controller;

use App\Models\SocialLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
class SocaialLoginController extends Controller
{
    public function social_login(Request $request)
    {
        $payLoad = json_decode($request->getContent(), true);
        $email       =$payLoad['email'];
        $name        =$payLoad['name'];
        $provider    =$payLoad['provider'];
        $token       =$payLoad['token'];
        $provider_pic=$payLoad['provider_pic'];
        $provider_id =$payLoad['provider_id'];

        DB::table('customer')->insert([
            'email' => $email,
            'customer_name' =>$name,
            'provider'=>$provider,
            'provider_id' =>$provider_id,
            'provider_pic'=>$provider_pic,
            'token'=>$token 
        ]);

        $userData=$this->internalUserDetails($email);

        return $userData;
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
