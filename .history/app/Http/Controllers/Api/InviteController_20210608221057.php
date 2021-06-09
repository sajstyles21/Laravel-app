<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invite;
use App\Mail\SendInvite;
use App\Http\Requests\InviteRequest;
use Mail;
use Str;

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
     *          name="email",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="email"
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/ApiModel")
     *       ),
     *     )
     */
    public function sendInvite(InviteRequest $request){
        try {
           //Generate token
           $token = Str::random();
           //check token exists
           $invoice = Invite::where(['token'=>$token])->first();
           if($invoice){
            return response([
                'success' => 0, 'statuscode' => 400,
                'message' => __('Token already exists. Please try again!')
            ], 400);
           }else{
            //create invoice
            $invoice_created = Invite::create(['email'=>$request->email,'token'=>$token]);
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

    /**
     * @OA\Get(
     *      path="/api/link",
     *      operationId="users.link",
     *      tags={"User"},
     *      summary="User get link",
     *      description="User get link",
     *      @OA\Parameter(ref="#/components/parameters/X-localization"),
     *      @OA\Parameter(
     *          name="username",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="password",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="password"
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/ApiModel")
     *       ),
     *     )
     */
    public function getLink(Request $request){
        try {
           echo "As";
           die();
           
        } catch (\Exception $e) {
            return response(['success' => 0, 'statuscode' => 400, 'message' => $e->getMessage()], 400);
        }
    }
}
