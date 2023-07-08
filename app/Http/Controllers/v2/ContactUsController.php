<?php

namespace App\Http\Controllers\v2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\views\contact;
use Mail;
use Validator;

class ContactUsController extends Controller
{
    public function contactUsSubmit(Request $request){

        try {

            $validation_array = [
                "name"       => 'required',
                "email"      => 'required|string|email|max:100',
                "bodyText"   => 'required',
    
            ];
            
    
            $validator = Validator::make($request->all(), $validation_array);
    
            if($validator->fails()){
                return response(['success' => false,'data'=> null,'message' => implode(" / ",$validator->messages()->all())], 200);  
            }

            Mail::send('mail.contact',
            array(
                'name'     => $request->get('name'),
                'email'    => $request->get('email'),
                'bodyText' => $request->get('bodyText'),
            ), function($message) use ($request)
            {
                $message->from($request->email);
                $message->subject('Contact us');
                $message->to('testback@backend.dropx.lk');
            });

            
            return response()->json(['success' => true,'data'=> null,'message' => 'Email successfully submited'], 201);  
          
        } catch (\Exception $e) {   
            return response()->json(['success' => false,'data'=> null,'message' => 'Oops! Something went wrong please try again later'], 500);     
        }

    }
}
