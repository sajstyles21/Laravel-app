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
}
