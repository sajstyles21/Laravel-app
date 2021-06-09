<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Auth;
use Illuminate\Http\Request;
use Mail;

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
     *                 @OA\Property(
     *                     property="address",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="city",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="county",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="skills",
     *                     type="string",
     *                     description="comma separated format (multiple skills)" 
     *                 ),
     *                 @OA\Property(
     *                     property="competencies",
     *                     type="string",
     *                     description="comma separated format (multiple competencies)" 
     *                 ),
     *                 @OA\Property(
     *                     property="latitude",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="longitude",
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
    public static function profileUpdate(Request $request)
    {
        try {
            $user = Auth::user()->load('competencies', 'whistory','skills');
            $validator = Validator::make($request->all(), [
                'first_name' => 'required',
                'last_name' => 'required',
                'postcode' => 'required',
                'address' => 'required',
                'city' => 'required',
                'county' => 'required',
                'skills' => 'required',
                'competencies' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
            ]);

            if ($validator->fails()) {
                return response(array('success' => 0, 'statuscode' => 400, 'message' =>
                $validator->getMessageBag()->first()), 400);
            }

            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->mobile = $request->mobile;
            $user->address = $request->address;
            $user->city = $request->city;
            $user->county = $request->county;
            $user->postcode = $request->postcode;
            $user->latitude = $request->latitude;
            $user->longitude = $request->longitude;
            if ($request->skills) {
                $skills = $request->skills;
                $input['skills'] = $skills;
                $skillsupdate = \App\CandidateSkill::updateOrCreate(['user_id' => $user->id], $input);
            }
            if ($request->competencies) {
                $competencies = $request->competencies;
                $input['competencies'] = $competencies;
                $compsupdate = \App\Competencies::updateOrCreate(['user_id' => $user->id], $input);
            }

            if ($user->save()) {
                $userdata = User::find($user->id)->load('competencies', 'whistory','skills');
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
