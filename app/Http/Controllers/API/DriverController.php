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
        $user = auth('sanctum')->user();
        $schedules = Schedule::where('status', 'pending')->get();

        $schedules->map(function($item) {
            $item->customer = User::find($item->user_id_customer)->name;
            return $item;
        });

        return response()->json([
            'success' => true,
            'user' => $user->name,
            'data' => $schedules
        ]);
    }

    public function show(Request $request, $id)
    {
        $schedule = Schedule::where('id', $id)->where('status', 'pending')->get();

        if ($schedule->isEmpty()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $schedule->map(function($item) {
            $item->customer = User::find($item->user_id_customer)->name;
            $item->address = User::find($item->user_id_customer)->address;
            return $item;
        });
        
        return response()->json([
            'data' => $schedule
        ]);
    }

    public function pickup(Request $request)
    {
        $schedule = Schedule::find($request->id);

        if ($schedule == null) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }elseif ($schedule->user_id_driver != null) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $driver = auth('sanctum')->user()->id;

        $schedule->update([
            'status' => 'on the way',
            'user_id_driver' => $driver
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully',
            'data' => $schedule
        ]);
    }

    public function history()
    {
        $user = auth('sanctum')->user();
        $schedules = Schedule::where('user_id_driver', $user->id)->get();

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
        $schedule = Schedule::where('id', $id)->where('user_id_driver', auth('sanctum')->user()->id)->get();

        if ($schedule->isEmpty()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $schedule->map(function($item) {
            $item->customer = User::find($item->user_id_customer)->name;
            $item->driver = User::find($item->user_id_driver)->name;
            return $item;
        });

        return response()->json([
            'data' => $schedule
        ]);
    }

    public function inputTransaction(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            'date' => 'required|date',
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
        
        $schedule = Schedule::where('id', $id)->where('user_id_driver', auth('sanctum')->user()->id)->get();
        
        if ($schedule->isEmpty()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $schedule = $schedule->first();
        
        if ($schedule->status != 'on the way') {
            return response()->json([
                'message' => 'Schedule not on the way'
            ], 401);
        }
        
        $driver = auth('sanctum')->user()->id;
        $customer = $schedule->user_id_customer;
        $users = [$driver, $customer];
        
        
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
        $userCustomer = User::find($customer);
        $userCustomer->update([
            'hold_balance' => $userCustomer->hold_balance + $total_price
        ]);

        $schedule->update([
            'status' => 'completed',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully',
            'data' => $transaction
        ]);
    }
}
