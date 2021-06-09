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

    /**
     * @OA\Post(
     *      path="/api/invite",
     *      operationId="users.invite",
     *      tags={"User"},
     *      summary="User send invite",
     *      description="User send invite",
     *      @OA\Parameter(ref="#/components/parameters/X-localization"),
     *      @OA\Parameter(
     *          name="job_application_id",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/ApiModel")
     *       ),
     *      security={
     *         {"Bearer": {}}
     *     }
     *     )
     */
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
                return response([
                    'success' => 1, 'statuscode' => 200,
                    'message' => __('Invitation sent successfully!')
                ], 200);
            }
            return response([
                'success' => 0, 'statuscode' => 400,
                'message' => __('Please try again!')
            ], 400);
           }
        } catch (\Exception $e) {
            return response(['success' => 0, 'statuscode' => 400, 'message' => $e->getMessage()], 400);
        }
    }
}
