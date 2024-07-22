<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Transaction;

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

    public function inputTransaction(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            'date' => 'required|date',
            'schedule_id' => 'required',
            'type_trash' => 'required|array',
            'price' => 'required|array',
            'weight' => 'required|array',
        ]);

        if ($validate->fails()) {
            return response()->json($validate->errors(), 422);
        }

        // Calculate total price
        $total_price = 0;
        for ($i = 0; $i < count($request->type_trash); $i++) {
            $total_price += $request->price[$i] * $request->weight[$i];
        }
        
        $schedule = Schedule::find($id);
        
        $driver = auth('sanctum')->user();
        $customer = $schedule->user;
        $users = [$driver->id, $customer->id];
        
        $transaction = Transaction::create([
            'date' => $request->date,
            'schedule_id' => $schedule->id,
            'type_trash' => json_encode($request->type_trash),
            'price' => json_encode($request->price),
            'weight' => json_encode($request->weight),
            'total_price' => $total_price, // Tambahkan total_price di sini
        ]);

        $transaction->users()->sync($users, true); 

        // Update hold_balance
        $customer->update([
            'hold_balance' => $customer->hold_balance + $total_price
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully',
            'data' => $transaction
        ]);
    }
}
