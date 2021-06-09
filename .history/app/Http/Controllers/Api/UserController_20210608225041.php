<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use App\Http\Requests\LoginRequest;
use Validator;
use Illuminate\Support\Facades\Storage;
use Auth;
use Mail;
use DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
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
     *      path="/api/login",
     *      operationId="users.Login",
     *      tags={"User"},
     *      summary="User login",
     *      description="User login",
     *      @OA\Parameter(ref="#/components/parameters/X-localization"),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                  required={"email", "password"},
     *                 @OA\Property(
     *                     property="email",
     *                     type="string"
     *                 ),
     *                  @OA\Property(
     *                     property="password",
     *                     type="password"
     *                 )
     *             )
     *         )
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/ApiModel")
     *       ),
     *      
     *     )
     */
    public static function login(LoginRequest $request)
    {
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            if ($user->hasRole('Candidate')) {
                $token = $user->createToken('laravel-app')->accessToken;
                $user->token = $token;
                if ($user->status != 3 || !in_array($user->profile_status,[1,3])) {
                    Auth::guard('web')->logout();
                    return Response(array('success' => 0, 'statuscode' => 400, 'message' => __('We are sorry, this candidate is not registered with us.')), 400);
                }else{
                    return response([
                        'success' => 1, 'statuscode' => 200,
                        'message' => __('login successfully !'), 'data' => ($user)
                    ], 200);
                }
                
            } else {
                Auth::guard('web')->logout();
                return Response(array('success' => 0, 'statuscode' => 400, 'message' => __('We are sorry, this candidate is not registered with us.')), 400);
            }
        } else {
            return Response(array('success' => 0, 'statuscode' => 400, 'message' => __('Invalid credentials')), 400);
        }
    }

    /**
     * @OA\Post(
     *      path="/api/logout",
     *      operationId="users.logout",
     *      tags={"User"},
     *      summary="User Logout",
     *      description="User Logout",
     *  @OA\Parameter(ref="#/components/parameters/X-localization"),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *         )
     *     ),
     *       @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/ApiModel")
     *       ),
     *     security={
     *         {"Bearer": {}}
     *     }
     *     )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function logout(Request $request)
    {
        $token = $request->user()->token();
        $token->revoke();

        return response([
            'success' => 1, 'statuscode' => 200,
            'message' => __('logout successfully !'), 'data' => []
        ], 200);
    }

}
