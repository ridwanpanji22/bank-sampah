<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Schedule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Http\Resources\ScheduleResource;

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
            'pickup_date' => 'required|date',
            'pickup_time' => 'required',
        ]);

        if ($validated->fails()) {
            return response()->json($validated->errors());
        }

        $schedules = Schedule::where('user_id_customer', auth('sanctum')->user()->id)
                        ->where('pickup_date', $request->pickup_date)
                        ->orWhere('user_id_customer', auth('sanctum')->user()->id)
                        ->where('status', 'pending')
                        ->orWhere('status', 'on the way')
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
            'user_id_customer' => $request->user()->id,
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

    public function statusSchedule(Request $request)
    {   
        $schedule = Schedule::where('user_id_customer', $request->user()->id)
                            ->where('status', 'pending')
                            ->orWhere('status', 'on the way')
                            ->get();
        
        $schedule->map(function($item) {
            if ($item->status == 'on the way') {
                $item->driver = User::find($item->user_id_driver)->name;
                return $item;
            }
        });
        
        return response()->json([
            'data' => $schedule
        ]);
    }

    public function history()
    {
        $schedules = Schedule::where('user_id_customer', auth('sanctum')->user()->id)->get();

        return response()->json([
            'data' => $schedules
        ]);
    }

    public function historyDetail($id)
    {
        $schedule = Schedule::where('id', $id)->where('user_id_customer', auth('sanctum')->user()->id)->get();

        if ($schedule->isEmpty()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $schedule->map(function($item) {
            if ($item->status !== 'pending') {
                $item->driver = User::find($item->user_id_driver)->name;
                return $item;
            }
        });
        return response()->json([
            'data' => $schedule
        ]);
    }
}
