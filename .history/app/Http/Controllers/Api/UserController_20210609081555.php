<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ProfileRequest;
use Auth;
use App\Models\User;
use Illuminate\Http\Request;
use Mail;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

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
            $token = $user->createToken('laravel-app')->accessToken;
            $user->token = $token;
            if ($user->status != 1) {
                Auth::logout();
                return Response(array('success' => 0, 'statuscode' => 400, 'message' => __('We are sorry, this user is not registered with us.')), 400);
            } else {
                return response([
                    'success' => 1, 'statuscode' => 200,
                    'message' => __('login successfully !'), 'data' => ($user),
                ], 200);
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
            'message' => __('logout successfully !'), 'data' => [],
        ], 200);
    }

    /**
     * @OA\Post(
     *      path="/api/profile_update",
     *      operationId="users.profileupdate",
     *      tags={"User"},
     *      summary="User profile update",
     *      description="User profile update",
     *      @OA\Parameter(ref="#/components/parameters/X-localization"),
     *      @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","user_name","avatar","email","user_role"},
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="user_name",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="avatar",
     *                     type="file",
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="user_role",
     *                     type="string",
     *                 ),
     *             )
     *         )
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/ApiModel")
     *       ),
     * security={
     *         {"Bearer": {}}
     *     }
     *      
     *     )
     */
    public static function profileUpdate(ProfileRequest $request)
    {
        try {
            $user = Auth::user();
            $user->name = $request->name??null;
            $user->user_name = $request->user_name??null;
            $user->email = $request->email??null;
            $user->user_role = $request->user_role??null;
            $user->registered_at = Carbon::now();
            if ($request->hasFile('avatar')) {
                //  Let's do everything here
                if ($request->file('avatar')->isValid()) {
                    $file = $request->file('avatar');
                    $folder = '';
                    $name = time() . '_' . $request->avatar->getClientOriginalName();
                    $file = $file->storeAs($folder, $name, 'users');
                    $url = Storage::disk('users')->url($name);
                    $user->avatar = $url;
                }
            }
            if ($user->save()) {
                $userdata = User::find($user->id);
                return response([
                    'success' => 1, 'statuscode' => 200,
                    'message' => __('Profile successfully updated.'), 'data' => $userdata
                ], 200);
            } else {
                return response([
                    'success' => 0, 'statuscode' => 400,
                    'message' => __('Please try again!'), 'data' => []
                ], 400);
            }
        } catch (\Exception $e) {
            return response(['success' => 0, 'statuscode' => 400, 'message' => $e->getMessage()], 400);
        }
    }

}
