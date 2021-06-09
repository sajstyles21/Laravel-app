<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InviteRequest;
use App\Mail\SendInvite;
use App\Mail\SendPin;
use App\Models\Invite;
use App\Models\User;
use Hash;
use Illuminate\Http\Request;
use Mail;
use Str;
use Validator;

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
     *      path="/api/send_invite",
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
    public function sendInvite(InviteRequest $request)
    {
        try {
            //Generate token
            $token = Str::random();
            //check token exists
            $invoice = Invite::where(['token' => $token])->first();
            if ($invoice) {
                return response([
                    'success' => 0, 'statuscode' => 400,
                    'message' => __('Token already exists. Please try again!'),
                ], 400);
            } else {
                //create invoice
                $invoice_created = Invite::create(['email' => $request->email, 'token' => $token]);
                if ($invoice_created) {
                    Mail::to($request->email)->send(new SendInvite($invoice_created));
                    return response([
                        'success' => 1, 'statuscode' => 200,
                        'message' => __('Invitation sent successfully!'),
                    ], 200);
                }
                return response([
                    'success' => 0, 'statuscode' => 400,
                    'message' => __('Please try again!'),
                ], 400);
            }
        } catch (\Exception $e) {
            return response(['success' => 0, 'statuscode' => 400, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Get(
     *      path="/api/get_user_data/{token}",
     *      operationId="users.data",
     *      tags={"User"},
     *      summary="User get data",
     *      description="User get data",
     *      @OA\Parameter(ref="#/components/parameters/X-localization"),
     *      @OA\Parameter(
     *          name="user_name",
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
     *              type="string"
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="token",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/ApiModel")
     *       ),
     *     )
     */
    public function getUserData(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_name' => 'required|string',
                'password' => 'required|string',
            ]);
            if ($validator->fails()) {
                return response(array('success' => 0, 'statuscode' => 400, 'message' =>
                    $validator->getMessageBag()->first()), 400);
            }
            //get invite from token
            $invite = Invite::where('token', $request->token)->first();
            if ($invite) {
                $email = User::where(['email' => $invite->email])->first();
                if (!$email) {
                    $user = User::create(['email' => $invite->email, 'user_name' => $request->user_name, 'password' => Hash::make($request->password), 'status' => 0]);
                    $pin = Str::random(6);
                    $data = [
                        'name' => $request->user_name,
                        'pin' => $pin,
                        'token' => $invite->token,
                    ];
                    Mail::to($invite->email)->send(new SendPin($data));
                    $invite->pin = $pin;
                    $invite->save();
                    return response([
                        'success' => 1, 'statuscode' => 200,
                        'message' => __('6 digit pin sent to your email!'),
                    ], 200);
                }
                return response([
                    'success' => 0, 'statuscode' => 400,
                    'message' => __('Email not available or pin already generated!'),
                ], 400);

            }
            return response([
                'success' => 0, 'statuscode' => 400,
                'message' => __('No invite available!'),
            ], 400);
        } catch (\Exception $e) {
            return response(['success' => 0, 'statuscode' => 400, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Get(
     *      path="/api/confirm_pin/{token}",
     *      operationId="users.pin",
     *      tags={"User"},
     *      summary="User confirm pin",
     *      description="User confirm pin",
     *      @OA\Parameter(ref="#/components/parameters/X-localization"),
     *      @OA\Parameter(
     *          name="pin",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/ApiModel")
     *       ),
     *     )
     */
    public function confirmPin(Request $request)
    {
        
        try {
            //get invite from token
            $invite = Invite::where('token', $request->token)->first();
            if ($invite) {
                //create user
                $user = User::where(['email' => $invite->email])->update(['status' => 1]);
                if ($user) {
                    $invite->delete();
                    return response([
                        'success' => 1, 'statuscode' => 200,
                        'message' => __('User registered successfully!'),
                    ], 200);
                }
                return response([
                    'success' => 0, 'statuscode' => 400,
                    'message' => __('No user available!'),
                ], 400);
            }
            return response([
                'success' => 0, 'statuscode' => 400,
                'message' => __('No invite available!'),
            ], 400);
        } catch (\Exception $e) {
            return response(['success' => 0, 'statuscode' => 400, 'message' => $e->getMessage()], 400);
        }
    }
}
