<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Team;

class TeamManagerController extends Controller
{

 public function index(Request $request)
{
    try {
        $teams = Team::with(['teamManagerData', 'teamLeadData'])
            ->active()
            ->orderByDesc('team_id')
            ->get();

        $formattedData = $teams->map(function ($team) {
            return [
                'id'                 => $team->team_id,
                'name'               => $team->team_name,
                'team_manager_id'    => $team->team_manager,
                'team_manager_name'  => $this->getFullName($team->teamManagerData),
                'team_lead_id'       => $team->sub_team_lead,
                'team_lead_name'     => $this->getFullName($team->teamLeadData),
                'status'             => $team->team_status
            ];
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Teams listed successfully.',
            'data'    => $formattedData
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Failed to fetch teams.',
            'error'   => $e->getMessage()
        ], 500);
    }
}

private function getFullName($user)
{
    if (!$user) {return null;}

    return trim(($user->admin_fname ?? '') . ' ' . ($user->admin_lname ?? '')) ?: null;
}


    public function edit(Request $request)
    {
        $team = Team::select(
     'team_id as id',
            'team_name as name',
            'team_manager as team_manager_id',
            'sub_team_lead as team_lead_id',
            'team_status as status'
        )->find($request->id);

        if (!$team) {
            return response()->json([
                'status' => 'error',
                'message' => 'Team not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Team details retrieved successfully.',
            'data' => $team
        ], 200);
    }

    /**
     * Store or update team record.
     */
    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'team_manager_id'   => 'required|integer',
            'team_lead_id'  => 'required|integer',
            'status'         => 'nullable|in:active,inactive',
            'id'             => 'nullable|exists:tbl_team,team_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $isUpdate = !empty($request->id);

        Team::updateOrCreate(
            ['team_id' => $request->id],
            [
                'team_name'    => $request->name,
                'team_manager' => $request->team_manager_id,
                'sub_team_lead'=> $request->team_lead_id,
                'team_status'  => $request->status ?? 'active',
             
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => $isUpdate ? 'Team updated successfully.' : 'Team created successfully.'
        ], 200);
    }

    /**
     * Delete team.
     */
    public function destroy(Request $request)
    {
        $team = Team::find($request->id);

        if (!$team) {
            return response()->json([
                'status' => 'error',
                'message' => 'Team not found.'
            ], 404);
        }

        $team->deleteflag = 'inactive';
        $team->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Team deleted successfully.'
        ], 200);
    }

    /**
     * Update team status.
     */
    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive',
            'id'     => 'required|exists:tbl_team,team_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $team = Team::find($request->id);
        $team->team_status = $request->status;
        $team->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Team status updated successfully.'
        ], 200);
    }
}
