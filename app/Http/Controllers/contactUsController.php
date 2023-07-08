<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\views\contact;
use Mail;
class contactUsController extends Controller
{
    // public function contactUsSubmit(Request $request){
    //     $data = array(
    //         'name' => $request->name,
    //         'email' => $request->email, 
    //         'subject' => $request->subject,
    //         'bodyText' => $request->bodyText
    //     );

    //     Mail::send('mail.contact',$data,
    //         function ($message) use ($data){
    //             $message->from($data['email']);
    //             $message->to('inquiry@foyo.lk');
    //             $message->subject($data['subject']);
    // });    
    // return response()->json([
    //     'message' => 'Contact detail submited',], 201);
    // }

    public function contactUsSubmit(Request $request){
        Mail::send('mail.contact',
            array(
                'name' => $request->get('name'),
                'email' => $request->get('email'),
                'subject' => $request->get('subject'),
                'bodyText' => $request->get('bodyText'),
            ), function($message) use ($request)
            {
                $message->from($request->email);
                $message->to('inquiry@foyo.lk');
            });

            return response()->json([
           'message' => 'Contact detail submited',], 201);
    }
}
