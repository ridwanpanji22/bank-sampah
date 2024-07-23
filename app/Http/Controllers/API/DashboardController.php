<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Schedule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $user = $request->user();
        return response()->json([
            'data' => $user
        ]);
    }

    public function createSchedule(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'pickup_date' => 'required',
            'pickup_time' => 'required',
        ]);

        if ($validated->fails()) {
            return response()->json($validated->errors());
        }

        $schedules = Schedule::where('user_id', $request->user()->id)
                        ->where('pickup_date', $request->pickup_date)
                        ->orWhere('user_id', $request->user()->id)
                        ->where('created_at', 'like', $request->pickup_date.'%')
                        ->get();

        if (!$schedules->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'You can only create one schedule per day',
                'data' => $schedules
            ], 409); // 409 Conflict
        }

        $user = User::find($request->user()->id);
        $number_order = $user->name.'-'.Str::random(5);
        $status = 'pending';

        $schedule = Schedule::create([
            'user_id' => $request->user()->id,
            'number_order' => $number_order,
            'pickup_date' => $request->pickup_date,
            'pickup_time' => $request->pickup_time,
            'status' => $status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Schedule created successfully',
            'data' => $schedule
        ]);

    }
}
