<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Invite;
use App\Mail\SendInvite;
use Mail;

class InviteController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('api');
    }

    public function sendInvite(Request $request){

        try {
           //Generate token
           $token = str_random();
           //check token exists
           $invoice = Invoice::where(['token'=>$token])->first();
           if($invoice){
            return response([
                'success' => 0, 'statuscode' => 400,
                'message' => __('Token already exists. Please try again!')
            ], 400);
           }else{
            //create invoice
            $invoice_created = Invoice::create(['email'=>$request->email,'token'=>$token]);
            if($invoice_created){
                Mail::to($request->email)->send(new SendInvite($invoice_created));
            }
            return response([
                'success' => 0, 'statuscode' => 400,
                'message' => __('Token already exists. Please try again!')
            ], 400);
           }
            
        } catch (\Exception $e) {
            return response(['success' => 0, 'statuscode' => 400, 'message' => $e->getMessage()], 400);
        }
    }
}
