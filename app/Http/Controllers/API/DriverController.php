<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use Illuminate\Support\Str; 
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Trash;
use App\Models\Transaction;
use App\Mail\PickupNotification;
use Illuminate\Support\Facades\Mail;

class DriverController extends Controller
{
    public function index()
    {
        $user = auth('sanctum')->user();
        $schedules = Schedule::where('status', 'pending')
                    ->orwhere('status', 'on the way')->get();

        $schedules->map(function($item) {
            $item->customer = User::find($item->user_id_customer)->name;
            $item->address = User::find($item->user_id_customer)->address;
            return $item;
        });

        return response()->json([
            'success' => true,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'address' => $user->address,
                'phone' => $user->phone,
                'role' => $user->roles->pluck('name')[0],
            ],
            'data' => $schedules
        ]);
    }

    public function show(Request $request, $id)
    {
        $schedule = Schedule::where('id', $id)->where('status', 'pending')
                    ->orwhere('id', $id)->where('status', 'on the way')->get();

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

        $request->date = date('Y-m-d', strtotime($request->date));

        // Calculate total price
        $total_price = 0;
        for ($i = 0; $i < count($request->type_trash); $i++) {
            $total_price += $request->price[$i] * $request->weight[$i];
        }
        
        $schedule = Schedule::where('id', $id)->get();
        
        if ($schedule->isEmpty()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        if ($schedule->first()->status == 'completed') {
            return response()->json([
                'message' => 'Schedule is already completed'
            ], 401);
        }

        $schedule = $schedule->first();
        
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
            'user_id_driver' => $driver,
            'status' => 'completed',
        ]);
        
        if ($userCustomer) {
            // Mengirim email ke customer
            $schedule->driver = User::find($driver)->name;
            Mail::to($userCustomer->email)->send(new PickupNotification($schedule));

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://whats.neumediradev.my.id/api/create-message',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array(
                    'appkey' => 'dfe62aed-5c96-475e-804d-61d2024dc699',
                    'authkey' => 'cMVBjuZPzmWrfxod7mHkdbeSlFNxMIxJqLOTUDcAcJyuka5yDo',
                    'to' => '62'.$userCustomer->phone,
                    'message' => 'Your trash pickuped by '. $schedule->driver.' for order number '.$schedule->number_order.' is '.$schedule->status.'.',
                    'sandbox' => 'false'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
        } else {
            return response()->json([
                'message' => 'Customer not found for the schedule'
            ], 404);
        
        }
        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully',
            'data' => $transaction
        ]);
    }

    public function autoCreateSchedule($ccm)
    {
        $customer = User::where('ccm', $ccm)->get();
        $driver = auth('sanctum')->user()->id;

        $pickup_date = date('Y-m-d');
        $pickup_time = date('H:i:s');
        $status = 'on the way';
        $name_explode = explode(' ', $customer->pluck('name')->first());
        $first_name = $name_explode[0];
        $number_order = $first_name.'-'.Str::random(5);

        $schedule = Schedule::create([
            'user_id_customer' => $customer->pluck('id')->first(),
            'user_id_driver' => $driver,
            'number_order' => $number_order,
            'pickup_date' => $pickup_date,
            'pickup_time' => $pickup_time,
            'status' => $status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Schedule created successfully',
            'data' => $schedule
        ]);
    }

    public function trash()
    {
        $trash = Trash::all();

        return response()->json([
            'success' => true,
            'message' => 'Trash retrieved successfully',
            'data' => $trash
        ]);
    }

}
