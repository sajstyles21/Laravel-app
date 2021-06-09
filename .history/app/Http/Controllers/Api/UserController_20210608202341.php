<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Validator;
use Illuminate\Support\Facades\Storage;
use Auth;
use Mail;
use DB;
use Notification;
use Carbon\Carbon;
use Helper;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use UploadTrait;
    use PushNotification;
    use Certificates;
    
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
     *      tags={"Candidate"},
     *      summary="Candidate login",
     *      description="Candidate login",
     *      @OA\Parameter(ref="#/components/parameters/X-localization"),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                  required={"email", "password"},
     *                 @OA\Property(
     *                     property="email",
     *                     type="email"
     *                 ),
     *                  @OA\Property(
     *                     property="password",
     *                     type="password"
     *                 ),
     *                  @OA\Property(
     *                     property="device_type",
     *                     type="string",
     *                      enum={"android", "ios"}
     *                 ),
     *                  @OA\Property(
     *                     property="device_token",
     *                     type="string"
     *                 ),
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
    public static function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
            "device_type" => ['required', Rule::in(\App\DeviceToken::$deviceTypes)],
            "device_token" => ['required', 'string']
        ]);

        if ($validator->fails()) {
            return response(array('success' => 0, 'statuscode' => 400, 'message' =>
            $validator->getMessageBag()->first()), 400);
        }


        if (Auth::guard('web')->attempt(['email' => $request->email, 'password' => $request->password], $request->remember)) {
            $user = Auth::guard('web')->user()->load('certificates', 'whistory','skills','competencies');
            if ($user->hasRole('Candidate')) {
                $user->updateDeviceToken($request->get('device_type'), $request->get('device_token'));
                $user->revokeTokens();
                $token = $user->createToken('healthcare')->accessToken;
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
     *      tags={"Candidate"},
     *      summary="Candidate Logout",
     *      description="Candidate Logout",
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

    /**
     * @OA\Post(
     *      path="/api/forgot_password",
     *      operationId="usprofileers.ForgotPassword",
     *      tags={"Candidate"},
     *      summary="Candidate forgot password",
     *      description="Candidate forgot password",
     *      @OA\Parameter(ref="#/components/parameters/X-localization"),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                  required={"email"},
     *                 @OA\Property(
     *                     property="email",
     *                     type="email"
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
    public static function forgot_password(Request $request)
    {

        $validation = Validator::make(
            $request->all(),
            [
                'email' => 'bail|required',
            ]
        );

        if ($validation->fails()) {
            return response(array('success' => 0, 'statuscode' => 400, 'message' =>
            $validation->getMessageBag()->first()), 400);
        }

        $user = User::where('email', $request->email)->first();
        if (is_object($user) && ($user->hasRole('Candidate'))) {
            $data['user_id'] = $user->id;
            $password = Helper::v4();
            $data['new_password'] = Hash::make($password);
            $data['name'] = $user->first_name;
            $data['email'] = $user->email;
            User::whereId($data['user_id'])
                ->limit(1)
                ->update([
                    'password' => $data['new_password'],
                    'updated_at' => new \DateTime
                ]);
            $mail = \Mail::send(
                'templates.forgot_password',
                array('data' => $data, 'password' => $password),
                function ($message) use ($data) {
                    $message->to($data['email'], $data['name'])->subject('Healthcare - Forgot Password!');
                }
            );
        } else {
            return response(array('success' => 0, 'statuscode' => 400, 'message' =>
            __('This email is not registered with us !')), 400);
        }
        return response(['success' => 1, 'statuscode' => 200, 'message' => __('We have sent a temporary password in your email. Please check your email.')], 200);
    }

    /**
     * @OA\Post(
     *      path="/api/change-password",
     *      operationId="users.change-password",
     *      tags={"Candidate"},
     *      summary="Change the Candidate Password",
     *      description="Change the Candidate Password",
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                  required={"old_password", "new_password"},
     *                 @OA\Property(
     *                     property="old_password",
     *                     type="string"
     *                 ),
     *                  @OA\Property(
     *                     property="new_password",
     *                     type="string"
     *                 )
     *             )
     *         )
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/ApiModel")
     *       ),
     *      security={
     *         {"Bearer": {}}
     *     }
     *     )
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'different:old_password']
        ]);

        $user = Auth::guard('api')->user();

        if (\Hash::check($request->get('old_password'), $user->password)) {
            $user->password = bcrypt($request->get('new_password'));
            $user->save();

            return response([
                'success' => 1, 'statuscode' => 200,
                'message' => __('Password changed successfully !'), 'data' => ($user)
            ], 200);
        }

        return response(array('success' => 0, 'statuscode' => 400, 'message' =>
        __('Password is not matching!')), 400);
    }

    /**
     * @OA\Post(
     *      path="/api/device_tokens",
     *      operationId="users.device.tokens",
     *      tags={"Candidate"},
     *      summary="Update the Candidate Device token",
     *      description="Update the Candidate Device token",
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                  required={"device_type", "device_token"},
     *                 @OA\Property(
     *                     property="device_type",
     *                     type="string",
     *                      enum={"android", "ios"}
     *                 ),
     *                  @OA\Property(
     *                     property="device_token",
     *                     type="string"
     *                 ),
     *             )
     *         )
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/ApiModel")
     *       ),
     *      security={
     *         {"Bearer": {}}
     *     }
     *     )
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatedevicetokens(Request $request)
    {
        $request->validate([
            "device_type" => ['required', Rule::in(\App\DeviceToken::$deviceTypes)],
            "device_token" => ['required', 'string']
        ]);

        $user = Auth::user();

        $user->updateDeviceToken($request->get('device_type'), $request->get('device_token'));
        $user->revokeTokens();

        return response([
            'success' => 1, 'statuscode' => 200,
            'message' => __('Device tokens updated successfully !'), 'data' => ($user)
        ], 200);
    }

    /**
     * @OA\Get(
     *      path="/api/skills_competencies",
     *      operationId="users.skills&competencies",
     *      tags={"Candidate"},
     *      summary="Candidate Skills & Competencies",
     *      description="Candidate Skills & Competencies",
     *      @OA\Parameter(ref="#/components/parameters/X-localization"),
     *     @OA\Parameter(
     *          name="type",
     *          required=true,
     *          in="query",
     *          description="1=>skills,2=>competencies,4=>roles,3=>both",
     *          @OA\Schema(
     *              type="integer"
     *          ),
     *      ),
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
    public static function skills_competencies(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response(array('success' => 0, 'statuscode' => 400, 'message' =>
                $validator->getMessageBag()->first()), 400);
            }

            $user = Auth::user();
            if ($request->type == 1) {
                $allskills = \App\AllSkill::orderBy('name')->get();
                $data['skills'] = $allskills;
                return response([
                    'success' => 1, 'statuscode' => 200,
                    'message' => __('Skills fetched successfully!'), 'data' => ($data)
                ], 200);
            } elseif ($request->type == 2) {
                $allcomps = \App\AllCompetency::orderBy('name')->get();
                $data['competencies'] = $allcomps;
                return response([
                    'success' => 1, 'statuscode' => 200,
                    'message' => __('Competencies fetched successfully!'), 'data' => ($data)
                ], 200);
            } elseif ($request->type == 4) {
                $roles = \App\AllRole::orderBy('name')->get();
                $data['roles'] = $roles;
                return response([
                    'success' => 1, 'statuscode' => 200,
                    'message' => __('Roles fetched successfully!'), 'data' => ($data)
                ], 200);
            } else {
                $allskills = \App\AllSkill::orderBy('name')->get();
                $allcomps = \App\AllCompetency::orderBy('name')->get();
                $roles = \App\AllRole::orderBy('name')->get();
                $data['skills'] = $allskills;
                $data['competencies'] = $allcomps;
                $data['roles'] = $roles;
                return response([
                    'success' => 1, 'statuscode' => 200,
                    'message' => __('Skills, Competencies and Roles fetched successfully!'), 'data' => ($data)
                ], 200);
            }
        } catch (\Exception $e) {
            return response(['success' => 0, 'statuscode' => 400, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Get(
     *      path="/api/ratings",
     *      operationId="users.ratings",
     *      tags={"Candidate"},
     *      summary="Candidate Ratings",
     *      description="Candidate Ratings",
     *      @OA\Parameter(ref="#/components/parameters/X-localization"),
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
    public static function ratings(Request $request)
    {
        try {
            $data = [];
            $user = Auth::guard('api')->user();
            $avg = '0';
            $fivepercent = '0';
            $fourpercent = '0';
            $threepercent = '0';
            $twopercent = '0';
            $onepercent = '0';
            $efficient = Auth::user()->efficiency->sum('efficiency');
            $punctual = Auth::user()->punctuality->sum('punctuality');
            $quality = Auth::user()->quality->sum('quality');
            $total_efficiency = Auth::user()->efficiency->count();
            $total_punctual = Auth::user()->punctuality->count();
            $total_quality = Auth::user()->quality->count();
            $totalreviews = $total_efficiency + $total_punctual + $total_quality;
            $reviews = $efficient + $punctual + $quality;
            if ($totalreviews) {
                $fivestars = User::fivestarratings();
                $fourstars = User::fourstarratings();
                $threestars = User::threestarratings();
                $twostars = User::twostarratings();
                $onestars = User::onestarratings();
                $avg = number_format($reviews / $totalreviews, 1);
                $fivepercent = ($fivestars / $totalreviews * 100);
                $fourpercent = ($fourstars / $totalreviews * 100);
                $threepercent = ($threestars / $totalreviews * 100);
                $twopercent = ($twostars / $totalreviews * 100);
                $onepercent = ($onestars / $totalreviews * 100);
            }

            $ratings = Auth::user()->load('ratings.job.template.employer');
            $data['reviews'] = $totalreviews;
            $data['avg'] = $avg;
            $data['fivestars'] = $fivestars ?? 0;
            $data['fourstars'] = $fourstars ?? 0;
            $data['threestars'] = $threestars ?? 0;
            $data['twostars'] = $twostars ?? 0;
            $data['onestars'] = $onestars ?? 0;
            $data['fivepercent'] = $fivepercent;
            $data['fourpercent'] = $fourpercent;
            $data['threepercent'] = $threepercent;
            $data['twopercent'] = $twopercent;
            $data['onepercent'] = $onepercent;
            $data['ratings'] = $ratings->ratings;

            return response([
                'success' => 1, 'statuscode' => 200,
                'message' => __('Ratings fetched successfully!'), 'data' => ($data)
            ], 200);
        } catch (\Exception $e) {
            return response(['success' => 0, 'statuscode' => 400, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Get(
     *      path="/api/allfilters",
     *      operationId="users.allfilters",
     *      tags={"Candidate"},
     *      summary="Candidate All Filters",
     *      description="Candidate All Filters",
     *      @OA\Parameter(ref="#/components/parameters/X-localization"),
     *      @OA\Parameter(
     *          name="type",
     *          required=true,
     *          in="query",
     *          description="(1=>pendingjobs,2=>bookedjobs,3=>completedjobs,5=>missedjobs)", 
     *          @OA\Schema(
     *              type="integer"
     *          ),
     *      ),
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
    public static function allfilters(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'type' => 'required',
            ]);

            if ($validator->fails()) {
                return response(array('success' => 0, 'statuscode' => 400, 'message' =>
                $validator->getMessageBag()->first()), 400);
            }

            $employers = [];
            $data = [];
            $user = Auth::user();
            $roles = \App\AllRole::orderBy('name')->get();
            if ($request->all()) {
                if ($request->type == 1) {
                    $employers = Employer::select('id', 'company_name')->has('jobs.applicationssearch')->groupBy('id', 'company_name')->get();
                } elseif ($request->type == 2) {
                    $employers = Employer::whereHas("roles", function ($q) {
                        $q->where("name", "Employer");
                    })->whereHas('jobs', function ($q) use ($user) {
                        $q->whereRaw("find_in_set('" . $user->id . "',assign_to)");
                        $q->whereIn('status', [2, 3]);
                        $q->whereHas('jobshifts', function ($query) {
                            $query->where('shift_end_date', '>=', Carbon::now('Europe/London')->format('Y-m-d H:i:s'));
                        });
                    })->orderBy('created_at', 'desc')->get();
                } elseif ($request->type == 3) {
                    $employers = \App\Employer::whereHas("roles", function ($q) {
                        $q->where("name", "Employer");
                    })->whereHas('jobs', function ($q) use ($user) {
                        $q->whereRaw("find_in_set('" . $user->id . "',assign_to)");
                    })->has('jobs.completedMissedApplication')->orderBy('created_at', 'desc')->get();
                } elseif ($request->type == 5) {
                    $employers = Employer::whereHas("roles", function ($q) {
                        $q->where("name", "Employer");
                    })->whereHas('jobs', function ($q) use ($user) {
                        $q->whereRaw("find_in_set('" . $user->id . "',assign_to)");
                        $q->whereHas('jobshifts', function ($query) {
                            $query->where('shift_date', '<', Carbon::now('Europe/London')->format('Y-m-d H:i:s'));
                        });
                    })->has('jobs.missedApplication')->orderBy('created_at', 'desc')->get();
                }
            }

            $data = ['employers' => $employers, 'roles' => $roles];

            return response([
                'success' => 1, 'statuscode' => 200,
                'message' => __('Employers and roles fetched successfully!'), 'data' => ($data)
            ], 200);
        } catch (\Exception $e) {
            return response(['success' => 0, 'statuscode' => 400, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Get(
     *      path="/api/home",
     *      operationId="users.homescreen",
     *      tags={"Candidate"},
     *      summary="Candidate Home data",
     *      description="Candidate Home data",
     *      @OA\Parameter(ref="#/components/parameters/X-localization"),
     *     
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
    public static function home(Request $request)
    {
        try {
            $user = Auth::user();
            $sjobs = \App\Job::searchjobs();
            $pjobs = \App\Job::pendingjobs();
            $bjobs = \App\Job::bookedjobs();
            $cjobs = \App\Job::completedjobs();
            $invites = \App\Job::allinvitations();
            //$latestshift = \App\Job::latestjobshift();
            $latestshift = \App\Job::upcomingshifts();
            if ($latestshift == null) {
                $data['latest_shift'] = [];
            } else {
                $data['latest_shift'] = $latestshift;
            }
            $data['search_jobs'] = $sjobs;
            $data['pending_jobs'] = $pjobs;
            $data['booked_jobs'] = $bjobs;
            $data['completed_jobs'] = $cjobs;
            $data['invitations'] = $invites->count();

            $data['total_my_jobs'] = ($pjobs + $bjobs + $cjobs + $invites->count());
            $allskills = \App\AllSkill::orderBy('name')->get();
            $relevantskills = '';
            if (isset($user->skills->skills)) {
                $user->skills->skills = explode(',', @$user->skills->skills);
                if ($allskills) {
                    foreach ($allskills as $skill) {
                        if (in_array($skill->id, $user->skills->skills)) {
                            $relevantskills .= $skill->name . ', ';
                        }
                    }
                }
            }
            $relevantskills = rtrim($relevantskills, ', ');

            $pending_timesheets = \App\Job::where(function ($query) use ($user) {
                $query->whereRaw("find_in_set('" . $user->id . "',assign_to)");
            })->has('completedMissedApplication')->whereHas('jobSentApproval', function ($q) {
                $q->where('status', 0);
            })->count();

            $completed_timesheets = \App\Job::where(function ($query) use ($user) {
                $query->whereRaw("find_in_set('" . $user->id . "',assign_to)");
            })->has('completedMissedApplication')->whereHas('jobSentApproval', function ($q) {
                $q->where('status', 1);
            })->count();

            $approved_timesheets = \App\Job::where(function ($query) use ($user) {
                $query->whereRaw("find_in_set('" . $user->id . "',assign_to)");
            })->has('completedMissedApplication')->whereHas('jobSentApproval', function ($q) {
                $q->where('status', 3);
            })->count();

            $data['pending_timesheets'] = $pending_timesheets;
            $data['approved_timesheets'] = $approved_timesheets;
            $data['completed_timesheets'] = $completed_timesheets;
            $data['total_timesheets'] = ($approved_timesheets + $pending_timesheets);
            $rating = User::getavgrating($user->id);

            $data['id_card'] = ['name' => $user->name, 'id_number' => $user->id_number, 'dbs_number' => $user->dbs->dbs_number, 'role' => $user->roledetail->name, 'skills' => $relevantskills, 'rating'=>$rating];

            //Graphs

            $lastyear = Carbon::now('Europe/London')->subDays(365);
            $today = Carbon::now('Europe/London');
            $title = 'Earning';

            $invoices = \App\Invoice::select('id', 'pay', 'hours', 'charge', 'created_at')->where('created_at', '>=', $lastyear)->where('created_at', '<=', $today);
            $invoices->where('user_id', $user->id);
            $invoices = $invoices->get()->groupBy(function ($date) {
                return Carbon::parse($date->created_at)->format('W/Y');
            });

            $invoicecount = [];
            $invoiceArr = [];

            foreach ($invoices as $key => $value) {
                $totalcharge = 0;
                foreach ($value as $l => $charge) {
                    $totalcharge += $charge->charge;
                }
                $invoicecount[$key] = $totalcharge;
            }

            for ($i = 0; $i < 12; $i++) {
                $weeks[] = date("W/Y", strtotime(date('Y-m-d') . " -$i weeks"));
            }


            for ($i = 0; $i < count($weeks); $i++) {
                if (!empty($invoicecount[$weeks[$i]])) {
                    $invoiceArr[$weeks[$i]] = $invoicecount[$weeks[$i]];
                } else {
                    $invoiceArr[$weeks[$i]] = 0;
                }
            }

            $allinvoices = array_reverse($invoiceArr);
            if ($allinvoices) {
                foreach ($allinvoices as $k => $_user) {
                    $labels[] = $k;
                    $sets[] = round($_user, 2);
                }
            }

            $data['labels'] = $labels;
            $data['earning'] = $sets;
            $data['earnings'] = round(User::totalearnings(),2);
        
            return response([
                'success' => 1, 'statuscode' => 200,
                'message' => __('Home data fetched successfully!'), 'data' => ($data)
            ], 200);
        } catch (\Exception $e) {
            return response(['success' => 0, 'statuscode' => 400, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Get(
     *      path="/api/notifications",
     *      operationId="users.notifications",
     *      tags={"Candidate"},
     *      summary="Candidate Notifications",
     *      description="Candidate Notifications",
     *      @OA\Parameter(ref="#/components/parameters/X-localization"),
     *     @OA\Parameter(
     *          name="startdate",
     *          in="query",
     *          description="",
     *          @OA\Schema(
     *              type="string"
     *          ),
     *      ),
     *     @OA\Parameter(
     *          name="enddate",
     *          in="query",
     *          description="",
     *          @OA\Schema(
     *              type="string"
     *          ),
     *      ),
     *     @OA\Parameter(
     *          name="status",
     *          in="query",
     *          description="('All actions'=>1,'New timesheet added'=>2,'Job request accepted'=>3,'Job request rejected'=>4)",
     *          @OA\Schema(
     *              type="integer"
     *          ),
     *      ),
     *     @OA\Parameter(
     *               name="pageno",
     *               in="query",
     *              description="Page No.", 
     *          @OA\Schema(
     *              type="string"
     *          ),
     *           ),
     *      @OA\Parameter(
     *               name="pageoffset",
     *               in="query",
     *              description="Offset", 
     *          @OA\Schema(
     *              type="string"
     *          ),
     *           ),
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
    public static function notifications(Request $request)
    {
        try {
            $user = Auth::user();
            $notifications = $user->notifications()->where('type', '!=', 'App\Notifications\MessageSend');

            if (!empty($request->startdate) && !empty($request->enddate)) {
                $notifications->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->startdate)));
                $notifications->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->enddate)));
            }

            if (!empty($request->status)) {
                if ($request->status == 2) {
                    $notifications->where('type', 'App\Notifications\TimesheetSubmitted');
                } elseif ($request->status == 3) {
                    $notifications->where('type', 'App\Notifications\JobRequestAccepted');
                } elseif ($request->status == 4) {
                    $notifications->where('type', 'App\Notifications\JobRequestRejected');
                } else {
                    $notifications->where('notifiable_type', 'App\User');
                }
            } else {
                $notifications->where('notifiable_type', 'App\User');
            }

            if (!empty($request->pageno))
                $notifications->skip(($request->pageno - 1) * $request->pageoffset)->take($request->pageoffset);
            else
                $notifications->skip(0)->take(10);

            $notifications = $notifications->get();
            $notifies = [];
            if ($notifications->count()) {
                foreach ($notifications as $notification) {
                    if (in_array($notification->type, ['App\\Notifications\\JobRequestAccepted', 'App\\Notifications\\JobRequestRejected'])) {
                        $type = 'job';
                    } else {
                        $type = 'timesheet';
                    }
                    $notifies[] = ['id' => $notification->data['id'], 'type' => $type, 'title' => $notification->data['title'], 'body' => $notification->data['body'], 'date' => date('D - d M Y', strtotime($notification->created_at))];
                }
            }
            return response([
                'success' => 1, 'statuscode' => 200,
                'message' => __('Notifications fetched successfully!'), 'data' => ($notifies)
            ], 200);
        } catch (\Exception $e) {
            return response(['success' => 0, 'statuscode' => 400, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Get(
     *      path="/api/candidate_data",
     *      operationId="users.details",
     *      tags={"Candidate"},
     *      summary="Candidate Details",
     *      description="Candidate Details",
     *      @OA\Parameter(ref="#/components/parameters/X-localization"),
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
    public function candidateData(Request $request)
    {
        try {
            $user = Auth::user()->load('dbs','certificates','roledetail','secondaryroledetail','vaccines')->loadCount('vaccines');
            $rating = User::getavgrating($user->id);
            $user->rating = $rating;
            $user->profile_image = ($user->profile_image) ? asset($user->profile_image) : asset('images/user.png');

            $allskills = \App\AllSkill::orderBy('name')->get();
            $relevantskills = '';
            if (isset($user->skills->skills)) {
                $user->skills->skills = explode(',', @$user->skills->skills);
                if ($allskills) {
                    foreach ($allskills as $skill) {
                        if (in_array($skill->id, $user->skills->skills)) {
                            $relevantskills .= '' . $skill->name . ', ';
                        }
                    }
                }
                $user->skills->skills = implode(',', @$user->skills->skills);
            }
            $relevantskills = rtrim($relevantskills, ', ');

            $relevantcomps = '';
            $allcomps = \App\AllCompetency::orderBy('name')->get();
            if (isset($user->competencies->competencies)) {
                $user->competencies->competencies = explode(',', @$user->competencies->competencies);
                if ($allcomps) {
                    foreach ($allcomps as $comp) {
                        if (in_array($comp->id, $user->competencies->competencies)) {
                            $relevantcomps .= '' . $comp->name . ', ';
                        }
                    }
                }
                $user->competencies->competencies = implode(',', @$user->competencies->competencies);
            }
            $relevantcomps = rtrim($relevantcomps, ', ');

            $user->allskills = $relevantskills;
            $user->allcomps = $relevantcomps;

            return response([
                'success' => 1, 'statuscode' => 200,
                'message' => __('Candidate details fetched successfully!'), 'data' => ($user)
            ], 200);
        } catch (\Exception $e) {
            return response(['success' => 0, 'statuscode' => 400, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Get(
     *      path="/api/getprofile",
     *      operationId="users.getprofile",
     *      tags={"Candidate"},
     *      summary="Candidate Profile",
     *      description="Candidate Profile",
     *      @OA\Parameter(ref="#/components/parameters/X-localization"),
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
    public static function getProfile(Request $request)
    {
        try {
            $user = Auth::user()->load('certificates', 'whistory');
            $allskills = \App\AllSkill::orderBy('name')->get();
            $allcomps = \App\AllCompetency::orderBy('name')->get();
           
            $data['skills'] = $allskills;
            $data['competencies'] = $allcomps;

            return response([
                'success' => 1, 'statuscode' => 200,
                'message' => __('Profile fetched successfully!'), 'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response(['success' => 0, 'statuscode' => 400, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Get(
     *      path="/api/getaddress",
     *      operationId="users.getaddress",
     *      tags={"Candidate"},
     *      summary="Candidate Address",
     *      description="Candidate Address",
     *      @OA\Parameter(ref="#/components/parameters/X-localization"),
     *      @OA\Parameter(
     *          name="text",
     *          in="query",
     *          description="Post code",
     *          @OA\Schema(
     *              type="string"
     *          ),
     *      ),
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
    public static function getaddress(Request $request)
    {
        try {
            $user = Auth::user()->load('certificates', 'whistory');

            $validator = Validator::make($request->all(), [
                'text' => 'required',
            ]);

            if ($validator->fails()) {
                return response(array('success' => 0, 'statuscode' => 400, 'message' =>
                $validator->getMessageBag()->first()), 400);
            }

            $country_code = "uk";
            $page = 0;
            // Replace with your API key, test key is locked to NR14 7PZ postcode search
            $api_key = env('POSTCODE_API_KEY');
            $input = $request->text;
            // Grab the input text and trim any whitespace
            $input = trim($input);

            // Create an empty output object
            $output = new \stdClass();

            if ($input == "") {

                // Respond without calling API if no input supplied
                $output->error_message = "No input supplied";
            } else {

                // Create the URL to API including API key and encoded address
                $address_url = "https://ws.postcoder.com/pcw/" . $api_key . "/address/" . $country_code . "/" . urlencode($input) . "?page=" . $page . "&lines=2&postcodeonly=true&addtags=latitude,longitude";

                // Use cURL to send the request and get the output
                $session = curl_init($address_url);
                // Tell cURL to return the request data
                curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
                // Use application/json to specify json return values, the default is XML
                $headers = array('Content-Type: application/json');
                curl_setopt($session, CURLOPT_HTTPHEADER, $headers);

                // Execute cURL on the session handle
                $response = curl_exec($session);


                // Capture any cURL errors
                if ($response === false) {
                    $curl_error = curl_error($session);
                }

                // Capture the HTTP status code from the API
                $http_status_code = curl_getinfo($session, CURLINFO_HTTP_CODE);

                // Close the cURL session
                curl_close($session);

                if ($http_status_code != 200) {

                    if (@$curl_error) {

                        // Triggered if cURL failed for some reason
                        // Output the error captured by curl_error()
                        http_response_code(500);
                        $output->error_message = "cURL error occurred - " . $curl_error;
                        echo json_encode(['message' => $output->error_message]);
                        die();
                    } else {

                        // Triggered if API does not return 200 HTTP code
                        // More info - https://postcoder.com/docs/error-handling
                        // Here we will output a basic message with HTTP code
                        http_response_code($http_status_code);
                        $output->error_message = "HTTP error occurred - " . $http_status_code;
                        echo json_encode(['message' => $output->error_message]);
                        die();
                    }
                } else {

                    // Convert JSON into an object
                    $result = json_decode($response);

                    if (count($result) > 0) {

                        // Check for the morevalues element in last address
                        $last_address = end($result);

                        if (property_exists($last_address, "morevalues")) {

                            // Pass through the paging info when needed
                            $output->next_page = (int) $last_address->nextpage;
                            $output->num_of_addresses = (int) $last_address->totalresults;
                        } else {

                            $output->num_of_addresses = count($result);
                        }

                        $output->current_page = (int) $page;

                        // Output the list of addresses
                        $output->addresses = $result;
                    } else {

                        $output->error_message = "No addresses found";
                        echo json_encode(['message' => $output->error_message]);
                        die();
                    }
                }
            }

            if ($output) {
                $addresses = [];
                $lat = '';
                $long = '';
                $counties = [];
                $posttown = [];

                if (count($output->addresses) > 0) {
                    foreach ($output->addresses as $details) {
                        $counties[] = $details->county;
                        $posttown[] = $details->posttown;
                        $addresses[] = $details->summaryline;
                        $lat = $details->latitude;
                        $long = $details->longitude;
                    }
                }
            }

            $data['data'] = ['addresses' => $addresses, 'counties' => $counties, 'cities' => $posttown, 'latitude' => $lat, 'longitude' => $long];
            return response([
                'success' => 1, 'statuscode' => 200,
                'message' => __('Data fetched successfully!'), 'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response(['success' => 0, 'statuscode' => 400, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Post(
     *      path="/api/postratings",
     *      operationId="users.postratings",
     *      tags={"Candidate"},
     *      summary="Candidate to employer ratings",
     *      description="Candidate to employer ratings",
     *      @OA\Parameter(ref="#/components/parameters/X-localization"),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                  required={"emp_id","job_id","safe","caring","well_led"},
     *                 @OA\Property(
     *                     property="emp_id",
     *                     type="integer",
     *                     description="Employer Id" 
     *                 ),
     *                 @OA\Property(
     *                     property="job_id",
     *                     type="integer",
     *                 ),
     *                 @OA\Property(
     *                     property="safe",
     *                     type="integer",
     *                 ),
     *                 @OA\Property(
     *                     property="caring",
     *                     type="integer",
     *                 ),
     *                 @OA\Property(
     *                     property="well_led",
     *                     type="integer",
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
    public static function postratings(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'emp_id' => 'required|integer|exists:employer,id',
                'job_id' => 'required|integer|exists:jobs,id',
                'safe' => 'required|integer',
                'caring' => 'required|integer',
                'well_led' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response(array('success' => 0, 'statuscode' => 400, 'message' =>
                $validator->getMessageBag()->first()), 400);
            }

            $user = Auth::user();
            //Inputs
            $input['emp_id'] = $request->emp_id;
            $input['job_id'] = $request->job_id;
            $input['user_id'] = $user->id;
            $input['efficiency'] = $request->safe;
            $input['punctuality'] = $request->caring;
            $input['quality'] = $request->well_led;
            $ratingbelowthree = User::checkApiRating($request->all());
            if ($ratingbelowthree) {
                $input['status'] = 0;
            } else {
                $input['status'] = 1;
            }
            $rating = \App\EmployerRating::updateOrCreate(['user_id' => $user->id, 'job_id' => $request->job_id, 'emp_id' => $request->emp_id], $input);
            if ($rating) {
                $emp = Employer::findOrFail($input['emp_id']);
                NotifyEmployerAboutRating::dispatch($request->job_id, $emp);
                return response([
                    'success' => 1, 'statuscode' => 200,
                    'message' => __('Rating successfully added!'), 'data' => []
                ], 200);
            } else {
                return response([
                    'success' => 0, 'statuscode' => 400,
                    'message' => __('Please try again!'), 'data' => []
                ], 400);
            }
            return response([
                'success' => 0, 'statuscode' => 400,
                'message' => __('Please try again!'), 'data' => []
            ], 400);
        } catch (\Exception $e) {
            return response(['success' => 0, 'statuscode' => 400, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Post(
     *      path="/api/profile_update",
     *      operationId="users.profileupdate",
     *      tags={"Candidate"},
     *      summary="Candidate profile update",
     *      description="Candidate profile update",
     *      @OA\Parameter(ref="#/components/parameters/X-localization"),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"first_name","last_name","email","mobile","postcode","address","city","county","skills","competencies","latitude","longitude"},
     *                 @OA\Property(
     *                     property="first_name",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="last_name",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="email",
     *                 ),
     *                 @OA\Property(
     *                     property="mobile",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="postcode",
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

    /**
     * @OA\Post(
     *      path="/api/certificate_upload",
     *      operationId="users.certificate_upload",
     *      tags={"Candidate"},
     *      summary="Candidate certificate upload",
     *      description="Candidate certificate upload",
     *      @OA\Parameter(ref="#/components/parameters/X-localization"),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                  required={"name","document","date"},
     *                 @OA\Property(
     *                     property="name",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="document",
     *                     type="file",
     *                 ),
     *                 @OA\Property(
     *                     property="date",
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
    public static function certificateUpload(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'document' => 'required|max:2000',
                'date' => 'required',
            ]);

            if ($validator->fails()) {
                return response(array('success' => 0, 'statuscode' => 400, 'message' =>
                $validator->getMessageBag()->first()), 400);
            }

            $user = Auth::user();

            if ($request->hasFile('document')) {
                //  Let's do everything here
                if ($request->file('document')->isValid()) {
                    $cv = $request->file('document');
                    $folder = '';
                    $name = time() . '_' . $request->document->getClientOriginalName();

                    $file = $cv->storeAs($folder, $name, 'candidates');
                    $url = Storage::disk('candidates')->url($name);
                    if ($url) {
                        $input['name'] = $request->name;
                        $input['date_expiry'] = $request->date;
                        $input['type'] = '0';
                        $input['user_id'] = $user->id;
                        $input['file'] = $url;
                        $cvupload = \App\Certificate::create($input);
                    }
    
                    return response([
                        'success' => 1, 'statuscode' => 200,
                        'message' => __('Certificate uploaded!'), 'data' => []
                    ], 200);
                }
            }else{
                return response([
                    'success' => 0, 'statuscode' => 400,
                    'message' => __('Please upload document!'), 'data' => []
                ], 400);
            }
            return response([
                'success' => 0, 'statuscode' => 400,
                'message' => __('Please try again!'), 'data' => []
            ], 400);
        } catch (\Exception $e) {
            return response(['success' => 0, 'statuscode' => 400, 'message' => $e->getMessage()], 400);
        }
    }
}
