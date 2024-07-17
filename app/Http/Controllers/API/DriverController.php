<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;

class DriverController extends Controller
{
    public function index()
    {
        $schedules = Schedule::where('status', 'pending')->get();
        $schedules->load('user');
        return response()->json([
            'data' => $schedules
        ]);
    }

    public function show(Request $request, $id)
    {
        $schedule = Schedule::find($id);
        $schedule->load('user');
        return response()->json([
            'data' => $schedule
        ]);
    }

    public function pickup(Request $request, $id)
    {
        $schedule = Schedule::find($id);
        $schedule->update([
            'status' => 'on the way'
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully',
            'data' => $schedule
        ]);
    }
}
