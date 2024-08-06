<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Schedule;
use App\Models\Transaction;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Http\Resources\ScheduleResource;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth('sanctum')->user();

        $transactions = Transaction::whereHas('users', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with('users')->get();
    
        // Decode the JSON strings to arrays and calculate the total weight
        $total_trash = 0;
    
        foreach ($transactions as $transaction) {
            $weight = json_decode($transaction->weight, true);
    
            // Add the weights to the total weight
            if (is_array($weight)) {
                $total_trash += array_sum($weight);
            }
    
            // Update the transaction with decoded values for better readability
            $transaction->weight = $weight;
        }
    
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'address' => $user->address,
                'phone' => $user->phone,
                'ccm' => $user->ccm,
                'house_hold' => $user->house_hold,
                'withdrawable_balance' => $user->withdrawable_balance,
                'hold_balance' => $user->hold_balance,
                'role' => $user->roles->pluck('name')[0],
                'total_trash' => $total_trash,
            ]
        ]);
    }

    public function createSchedule(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'pickup_date' => 'required|date',
            'pickup_time' => 'required',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validated->errors(),
            ], 422);
        }

        $date = date('Y-m-d', strtotime($request->pickup_date));

        if ($date < date('Y-m-d')) {
            return response()->json([
                'success' => false,
                'message' => 'Pickup date cannot be in the past',
            ], 400); // 400 Bad Request
        }

        $user = auth('sanctum')->user();

        $schedules = Schedule::where('user_id_customer', auth('sanctum')->user()->id)
                        ->where('pickup_date', $request->pickup_date)
                        ->orWhere('user_id_customer', auth('sanctum')->user()->id)
                        ->where('status', 'pending')
                        ->orWhere('user_id_customer', auth('sanctum')->user()->id)
                        ->where('status', 'on the way')
                        ->get();

        if (!$schedules->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'You can only create one schedule per day'
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
                            ->orWhere('user_id_customer', $request->user()->id)
                            ->where('status', 'on the way')
                            ->get();
        
        $schedule->map(function($item) {
            if ($item->status == 'on the way') {
                $item->driver = User::find($item->user_id_driver)->name;
            }else {
                $item->driver = null;
            }
            $item->customer = User::find($item->user_id_customer)->name;
            $item->address = User::find($item->user_id_customer)->address;
            return $item;
        });

        if ($schedule->isEmpty()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        
        return response()->json([
            'data' => $schedule
        ]);
    }

    public function history()
    {
        $user = auth('sanctum')->user();
        $schedules = Schedule::where('user_id_customer', $user->id)->get();
        
        // Filter transactions based on the currently authenticated user
        $transactions = Transaction::whereHas('users', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->get();
        
        $formattedSchedules = $schedules->map(function($schedule) use ($transactions) {
            $scheduleTransactions = $transactions->where('schedule_id', $schedule->id);
            
            $totalWeight = $scheduleTransactions->sum(function($transaction) {
                $weights = json_decode($transaction->weight);
                return array_sum($weights);
            });
    
            return [
                'id' => $schedule->id,
                'user_id_driver' => $schedule->user_id_driver,
                'user_id_customer' => $schedule->user_id_customer,
                'number_order' => $schedule->number_order,
                'pickup_date' => $schedule->pickup_date,
                'pickup_time' => $schedule->pickup_time,
                'status' => $schedule->status,
                'total_weight' => $totalWeight,
                'total_price' => $scheduleTransactions->sum('total_price'),
            ];
        });
    
        return response()->json([
            'data' => $formattedSchedules
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
            }else {
                $item->driver = null;
            }
            return $item;
        });
        
        return response()->json([
            'data' => $schedule
        ]);
    }
}
