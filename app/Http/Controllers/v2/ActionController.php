<?php

namespace App\Http\Controllers\v2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Newsletter;
use Validator;
class ActionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Public function / create newsletter
    |--------------------------------------------------------------------------
    |
    */  
    public function createNewsletter(Request $request){

        try {

            $validation_array = [
                "email"           => 'required|email',
                "franchise"       => 'required',
            ];
            
            $validator = Validator::make($request->all(), $validation_array);
        
            if($validator->fails()){
                return response(['success' => false,'data'=> null,'message' => implode(" / ",$validator->messages()->all())], 200);  
            }
        
            $email         =$request->email;
            $franchise     =$request->franchise; 

            $newsletter = Newsletter::create(
                ['email'      => $email,
                'franchise'   => $franchise]);
        
            $data = array(
                'email'         =>   $newsletter->email,
                'franchise'     =>   $newsletter->franchise,
            );

            if($newsletter){
                return response(['success' => true,'data'=> $data,'message' => "Everything is good!. Newsletter successfully created"], 200);
            }

        } catch (\Exception $e) {
        
            return response(['success' => false,'data'=> null,'message' => "Something went wrong. Please try again later!", 'error' => $e->getMessage()], 500);
        }
    }
}
