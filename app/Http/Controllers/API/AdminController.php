<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str; 
use App\Models\User;
use App\Models\Schedule;
use App\Models\Transaction;
use App\Models\Sale;
use App\Models\Trash;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmail;
use Illuminate\Support\Facades\URL;

class AdminController extends Controller
{
    public function index()
    {
        $admin = auth('sanctum')->user();
        $users = User::latest()->get();
    
        // Mendapatkan semua sales dan transactions
        $sales = Sale::all();
        $transactions = Transaction::all();
    
        // Menghitung total harga dari sales
        $totalSalesPrice = $sales->sum(function($sale) {
            return (int) $sale->total_price;
        });
    
        // Menghitung total harga dari transactions
        $totalTransactionsPrice = $transactions->sum(function($transaction) {
            return (int) $transaction->total_price;
        });
    
        // Menghitung jumlah user berdasarkan role
        $roleCounts = $users->groupBy('roles.name')->map(function($group) {
            return $group->count();
        });
    
        // Mapping ulang data users untuk menambahkan field role dan mengembalikan dalam format yang diinginkan
        $users = $users->map(function($item) {
            $item->role = $item->roles->pluck('name')[0];
            return [
                'id' => $item->id,
                'name' => $item->name,
                'email' => $item->email,
                'ktp' => $item->ktp,
                'address' => $item->address,
                'phone' => $item->phone,
                'ccm' => $item->ccm,
                'house_hold' => $item->house_hold ?? '-',
                'withdrawable_balance' => 'Rp.' . number_format($item->withdrawable_balance, 0, ',', '.'),
                'hold_balance' => 'Rp.' . number_format($item->hold_balance, 0, ',', '.'),
                'role' => $item->role
            ];
        });

        // Menghitung jumlah user berdasarkan role
        $usersRole = $users->groupBy('role')->map(function($group) {
            return $group->count();
        });
    
        // Mengembalikan response dalam format JSON
        return response()->json([
            'success' => true,
            'admin' => $admin->name,
            'total_customers' => $usersRole->get('customer', 0),
            'total_drivers' => $usersRole->get('driver', 0),
            'total_sales_price' => 'Rp.' . number_format($totalSalesPrice, 0, ',', '.'),
            'total_transactions_price' => 'Rp.' . number_format($totalTransactionsPrice, 0, ',', '.'),
            'data' => $users
        ]);
    }       

    public function sendVerificationEmail(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Email already verified'
            ]);
        }
        $user->sendEmailVerificationNotification();
        // event(new Registered($user));

        return response()->json([
            'success' => true,
            'message' => 'Verification link sent!'
        ]);
    }
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'ktp' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8',
            'confirm_password' => 'required|same:password',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'role' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400); // 400 = bad request
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'ktp' => $request->ktp,
            'password' => Hash::make($request->password),
            'address' => $request->address,
            'phone' => $request->phone,
            'ccm' => Str::random(10),
            'house_hold' => $request->house_hold,
        ]);


        $user->assignRole($request->role);
        $user->getRoleNames();

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'ktp' => $user->ktp,
                'address' => $user->address,
                'phone' => $user->phone,
                'ccm' => $user->ccm,
                'house_hold' => $user->house_hold ?? '-',
                'role' => $user->roles->first()->name ?? null,
            ],
        ]);
    }

    public function show($id)
    {
        $user = User::find($id);
        $user->getRoleNames();

        return response()->json([
            'success' => true,
            'message' => 'User retrieved successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'ktp' => $user->ktp,
                'address' => $user->address,
                'phone' => $user->phone,
                'ccm' => $user->ccm,
                'house_hold' => $user->house_hold ?? '-',
                'withdrawable_balance' => 'Rp.' . number_format($user->withdrawable_balance, 0, ',', '.'),
                'hold_balance' => 'Rp.' . number_format($user->hold_balance, 0, ',', '.'),
                'role' => $user->roles->first()->name
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        if (!empty($request->password) || !empty($request->confirm_password)) {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255',
                'ktp' => 'required|string|max:255|unique:users',
                'password' => 'string|min:8',
                'confirm_password' => 'same:password',
                'address' => 'required|string|max:255',
                'phone' => 'required|string|max:255',
                'role' => 'required|string|max:255',
            ]);
    
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }
            $user = User::find($id);
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'ktp' => $request->ktp,
                'password' => Hash::make($request->password),
                'address' => $request->address,
                'phone' => $request->phone,
                'house_hold' => $request->house_hold,
                'withdrawable_balance' => $request->withdrawable_balance,
                'hold_balance' => $request->hold_balance,
                'role' => $request->role
            ]);
        }else{
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255',
                'address' => 'required|string|max:255',
                'phone' => 'required|string|max:255',
                'role' => 'required|string|max:255',
                'ktp' => 'required|string|max:255|unique:users',
            ]);
    
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $user = User::find($id);
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'ktp' => $request->ktp,
                'address' => $request->address,
                'phone' => $request->phone,
                'house_hold' => $request->house_hold,
                'withdrawable_balance' => $request->withdrawable_balance,
                'hold_balance' => $request->hold_balance,
                'role' => $request->role
            ]);
        }

        $user->syncRoles($request->role);
        $user->getRoleNames();

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'ktp' => $user->ktp,
                'address' => $user->address,
                'phone' => $user->phone,
                'ccm' => $user->ccm,
                'house_hold' => $user->house_hold ?? '-',
                'withdrawable_balance' => 'Rp.' . number_format($user->withdrawable_balance, 0, ',', '.'),
                'hold_balance' => 'Rp.' . number_format($user->hold_balance, 0, ',', '.'),
                'role' => $user->roles->first()->name ?? null,

            ],
        ]);
    }

    public function destroy($id)
    {
        $user = User::find($id);
        $user->delete();
        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }

    public function customers()
    {
        $users = User::whereHas('roles', function($query) {
            $query->where('name', 'customer');
        })->with('roles')->latest()->get();

        $users = $users->map(function($item) {
            $item->role = $item->roles->pluck('name')[0];
            return [
                'id' => $item->id,
                'name' => $item->name,
                'email' => $item->email,
                'ktp' => $item->ktp,
                'address' => $item->address,
                'phone' => $item->phone,
                'ccm' => $item->ccm,
                'house_hold' => $item->house_hold ?? '-',
                'withdrawable_balance' => 'Rp.' . number_format($item->withdrawable_balance, 0, ',', '.'),
                'hold_balance' => 'Rp.' . number_format($item->hold_balance, 0, ',', '.'),
                'role' => $item->role,
                'verified_at' => $item->verified_at
            ];
        });
        
        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
            'data' => $users
        ]);
    }
    
    public function drivers()
    {
        $users = User::whereHas('roles', function($query) {
            $query->where('name', 'driver');
        })->with('roles')->latest()->get();

        $users = $users->map(function($item) {
            $item->role = $item->roles->pluck('name')[0];
            return [
                'id' => $item->id,
                'name' => $item->name,
                'email' => $item->email,
                'ktp' => $item->ktp,
                'address' => $item->address,
                'phone' => $item->phone,
                'ccm' => $item->ccm,
                'house_hold' => $item->house_hold ?? '-',
                'withdrawable_balance' => 'Rp.' . number_format($item->withdrawable_balance, 0, ',', '.'),
                'hold_balance' => 'Rp.' . number_format($item->hold_balance, 0, ',', '.'),
                'role' => $item->role
            ];
        });
        
        return response()->json([
            'success' => true,
            'message' => ' successfully',
            'data' => $users
        ]);
    }

    public function userTransactions($id)
    {
        $user = User::find($id);
        $transactions = Transaction::whereHas('users', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with(['users.roles'])->latest('date')->get();
    
        $formattedTransactions = $transactions->map(function($transaction) {
            $type_trash = json_decode($transaction->type_trash);
            $price = json_decode($transaction->price);
            $weight = json_decode($transaction->weight);
    
            $trash = [];
            for ($i = 0; $i < count($type_trash); $i++) {
                $trash[] = [
                    'type_trash' => $type_trash[$i],
                    'price' => $price[$i],
                    'weight' => $weight[$i],
                ];
            }
    
            $customer = $transaction->users->first(function($user) {
                return $user->roles->contains('name', 'customer');
            });
    
            $driver = $transaction->users->first(function($user) {
                return $user->roles->contains('name', 'driver');
            });
    
            return [
                'id' => $transaction->id,
                'date' => $transaction->date,
                'schedule_id' => $transaction->schedule_id,
                'trash' => $trash,
                'customer_name' => $customer ? $customer->name : null,
                'driver_name' => $driver ? $driver->name : null,
            ];
        });
    
        return response()->json([
            'data' => $formattedTransactions,
        ]);
    }


    public function transactions()
    {
        $transactions = Transaction::latest()->get();
    
        $formattedTransactions = $transactions->map(function($transaction) {
            $type_trash = json_decode($transaction->type_trash);
            $price = json_decode($transaction->price);
            $weight = json_decode($transaction->weight);

            $trash = [];
            for ($i = 0; $i < count($type_trash); $i++) {
                $trash[] = [
                    'type_trash' => $type_trash[$i],
                    'price' => $price[$i],
                    'weight' => $weight[$i],
                ];
            }

            return [
                'id' => $transaction->id,
                'date' => $transaction->date,
                'schedule_id' => $transaction->schedule_id,
                'trash' => $trash,
                'created_at' => $transaction->created_at,
                'updated_at' => $transaction->updated_at,
            ];
        });

        return response()->json([
            'data' => $formattedTransactions
        ]);
    }

    public function createSale( Request $request )
    {
        $validate = Validator::make($request->all(), [
            'date' => 'required|date',
            'name' => 'required',
            'type_trash' => 'required|array',
            'price' => 'required|array',
            'weight' => 'required|array',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validate->errors(),
            ], 422);
        }
        
        $formattedDate = date('Y-m-d', strtotime($request->input('date')));

        $total_price = 0;
        for ($i = 0; $i < count($request->type_trash); $i++) {
            $total_price += $request->price[$i] * $request->weight[$i];
        }

        $total_weight = 0;
        for ($i = 0; $i < count($request->weight); $i++) {
            $total_weight += $request->weight[$i];
        }

        $sale = Sale::create([
            'date' => $formattedDate,
            'name' => $request->name,
            'type_trash' => json_encode($request->type_trash),
            'price' => json_encode($request->price),
            'weight' => json_encode($request->weight),
            'total_price' => $total_price,
            'total_weight' => $total_weight,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sale created successfully',
            'data' => $sale
        ]);
    }

    public function sales()
    {
        $sales = Sale::latest()->get();

        $formattedSales = $sales->map(function($sale) {
            $type_trash = json_decode($sale->type_trash);
            $price = json_decode($sale->price);
            $weight = json_decode($sale->weight);

            $trash = [];
            for ($i = 0; $i < count($type_trash); $i++) {
                $trash[] = [
                    'type_trash' => $type_trash[$i],
                    'price' => $price[$i],
                    'weight' => $weight[$i],
                ];
            }

            return [
                'id' => $sale->id,
                'date' => $sale->date,
                'name' => $sale->name,
                'trash' => $trash,
                'total_price' => $sale->total_price,
                'total_weight' => $sale->total_weight,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Sales retrieved successfully',
            'data' => $formattedSales
        ]);
    }

    public function salesDetail($id)
    {
        $sale = Sale::find($id);

        if (is_null($sale)) {
            return response()->json([
                'success' => false,
                'message' => 'Sale not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sale retrieved successfully',
            'data' => $sale
        ]);
    }

    public function salesUpdate(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'type_trash' => 'required|array',
            'price' => 'required|array',
            'weight' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 400);
        }

        $sale = Sale::find($id);
        
        if (is_null($sale)) {
            return response()->json([
                'success' => false,
                'message' => 'Sale not found',
            ], 401);
        }

        $total_price = 0;
        for ($i = 0; $i < count($request->type_trash); $i++) {
            $total_price += $request->price[$i] * $request->weight[$i];
        }

        $total_weight = 0;
        for ($i = 0; $i < count($request->weight); $i++) {
            $total_weight += $request->weight[$i];
        }

        $sale->update([
            'name' => $request->name,
            'type_trash' => json_encode($request->type_trash),
            'price' => json_encode($request->price),
            'weight' => json_encode($request->weight),
            'total_price' => $total_price,
            'total_weight' => $total_weight,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sale updated successfully',
            'data' => $sale
        ]);
    }

    public function salesDelete($id)
    {
        $sale = Sale::find($id);

        if (is_null($sale)) {
            return response()->json([
                'success' => false,
                'message' => 'Sale not found',
            ], 404);
        }

        $sale->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sale deleted successfully',
        ]);
    }

    public function createTrash( Request $request )
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required',
            'price' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validate->errors(),
            ], 422);
        }

        $trash = Trash::create([
            'name' => $request->name,
            'price' => $request->price,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trash created successfully',
            'data' => $trash
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

    public function trashDetail($id)
    {
        $trash = Trash::find($id);

        if (is_null($trash)) {
            return response()->json([
                'success' => false,
                'message' => 'Trash not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Trash retrieved successfully',
            'data' => $trash
        ]);
    }

    public function updateTrash( Request $request, $id )
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required',
            'price' => 'required',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validate->errors(),
            ], 422);
        }

        $trash = Trash::find($id);
        $trash->update([
            'name' => $request->name,
            'price' => $request->price,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trash updated successfully',
            'data' => $trash
        ]);
    }

    public function deleteTrash($id)
    {
        $trash = Trash::find($id);

        if (!$trash) {
            return response()->json([
                'success' => false,
                'message' => 'Trash not found',
            ], 404);
        }

        $trash->delete();

        return response()->json([
            'success' => true,
            'message' => 'Trash deleted successfully',
        ]);
    }

    public function salesReports(Request $request)
    {
        $start_date = $request->has('start_date') ? date('Y-m-d', strtotime($request->start_date)) : null;
        $end_date = $request->has('end_date') ? date('Y-m-d', strtotime($request->end_date)) : null;
        
        if ($start_date && $end_date) {
            $validate = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            if ($validate->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validate->errors(),
                ], 422);
            }

            $sales = Sale::whereBetween('date', [$start_date, $end_date])->get();
            $transactions = Transaction::whereBetween('date', [$start_date, $end_date])->get();

        } elseif ($start_date) {
            $validate = Validator::make($request->all(), [
                'start_date' => 'required|date',
            ]);

            if ($validate->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validate->errors(),
                ], 422);
            }

            $sales = Sale::whereDate('date', $start_date)->get();
            $transactions = Transaction::whereDate('date', $start_date)->get();

        } elseif ($end_date) {
            $validate = Validator::make($request->all(), [
                'end_date' => 'required|date',
            ]);

            if ($validate->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validate->errors(),
                ], 422);
            }

            $sales = Sale::whereDate('date', $end_date)->get();
            $transactions = Transaction::whereDate('date', $end_date)->get();

        } else {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid date range or month and year.'
            ], 400);
        }

        
        $formattedSales = $sales->map(function($sale) {
            $type_trash = json_decode($sale->type_trash);
            $price = json_decode($sale->price);
            $weight = json_decode($sale->weight);
            
            $trash = [];
            for ($i = 0; $i < count($type_trash); $i++) {
                $trash[] = [
                    'type_trash' => $type_trash[$i],
                    'price' => $price[$i],
                    'weight' => $weight[$i],
                ];
            }

            return [
                'id' => $sale->id,
                'date' => $sale->date,
                'name' => $sale->name,
                'trash' => $trash,
                'total_price' => 'Rp.' . number_format($sale->total_price, 0, ',', '.'),
                'total_weight' => number_format($sale->total_weight, 0, ',', '.') . ' kg',
            ];
        });
        
        $formattedTransactions = $transactions->map(function($transaction) {
            $type_trash = json_decode($transaction->type_trash);
            $price = json_decode($transaction->price);
            $weight = json_decode($transaction->weight);

            $trash = [];
            for ($i = 0; $i < count($type_trash); $i++) {
                $trash[] = [
                    'type_trash' => $type_trash[$i],
                    'price' => $price[$i],
                    'weight' => $weight[$i],
                ];
            }

            return [
                'id' => $transaction->id,
                'date' => $transaction->date,
                'trash' => $trash,
                'total_price' => 'Rp.' . number_format($transaction->total_price, 0, ',', '.'),
                'total_weight' => array_sum($weight),
            ];
        });
        
        // Total income and weight from sales
        $total_sales_income = $sales->sum('total_price');
        $total_sales_weight = $sales->sum('total_weight');
        
        // Total cost and weight from transactions
        $total_transaction_cost = $transactions->sum('total_price');
        $total_transaction_weight = $formattedTransactions->sum('total_weight');
        
        // Profit or Loss calculation
        $profit_or_loss = $total_sales_income - $total_transaction_cost;

        return response()->json([
            'success' => true,
            'message' => 'Sales and transactions retrieved successfully',
            'data' => [
                'sales' => $formattedSales,
                'profit_or_loss' => 'Rp.' . number_format($profit_or_loss, 0, ',', '.'),
                'total_sales_income' => 'Rp.' . number_format($total_sales_income, 0, ',', '.'),
                'total_sales_weight' => number_format($total_sales_weight, 0, ',', '.') . ' kg',
                'total_transaction_cost' => 'Rp.' . number_format($total_transaction_cost, 0, ',', '.'),
                'total_transaction_weight' => number_format($total_transaction_weight, 0, ',', '.') . ' kg'
                ]
        ]);
    }

    public function transactionsReports( Request $request )
    {
        $start_date = $request->has('start_date') ? date('Y-m-d', strtotime($request->start_date)) : null;
        $end_date = $request->has('end_date') ? date('Y-m-d', strtotime($request->end_date)) : null;
        
        if ($start_date && $end_date) {
            $validate = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            if ($validate->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validate->errors(),
                ], 422);
            }

            $sales = Sale::whereBetween('date', [$start_date, $end_date])->get();
            $transactions = Transaction::whereBetween('date', [$start_date, $end_date])->get();

        } elseif ($start_date) {
            $validate = Validator::make($request->all(), [
                'start_date' => 'required|date',
            ]);

            if ($validate->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validate->errors(),
                ], 422);
            }

            $sales = Sale::whereDate('date', $start_date)->get();
            $transactions = Transaction::whereDate('date', $start_date)->get();

        } elseif ($end_date) {
            $validate = Validator::make($request->all(), [
                'end_date' => 'required|date',
            ]);

            if ($validate->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validate->errors(),
                ], 422);
            }

            $sales = Sale::whereDate('date', $end_date)->get();
            $transactions = Transaction::whereDate('date', $end_date)->get();

        } else {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid date range or month and year.'
            ], 400);
        }

        
        $formattedSales = $sales->map(function($sale) {
            $type_trash = json_decode($sale->type_trash);
            $price = json_decode($sale->price);
            $weight = json_decode($sale->weight);
            
            $trash = [];
            for ($i = 0; $i < count($type_trash); $i++) {
                $trash[] = [
                    'type_trash' => $type_trash[$i],
                    'price' => $price[$i],
                    'weight' => $weight[$i],
                ];
            }

            return [
                'id' => $sale->id,
                'date' => $sale->date,
                'name' => $sale->name,
                'trash' => $trash,
                'total_price' => 'Rp.' . number_format($sale->total_price, 0, ',', '.'),
                'total_weight' => number_format($sale->total_weight, 0, ',', '.') . ' kg',
            ];
        });
        
        $formattedTransactions = $transactions->map(function($transaction) {
            $type_trash = json_decode($transaction->type_trash);
            $price = json_decode($transaction->price);
            $weight = json_decode($transaction->weight);

            $trash = [];
            for ($i = 0; $i < count($type_trash); $i++) {
                $trash[] = [
                    'type_trash' => $type_trash[$i],
                    'price' => $price[$i],
                    'weight' => $weight[$i],
                ];
            }

            return [
                'id' => $transaction->id,
                'date' => $transaction->date,
                'trash' => $trash,
                'total_price' => 'Rp.' . number_format($transaction->total_price, 0, ',', '.'),
                'total_weight' => array_sum($weight),
            ];
        });
        
        // Total income and weight from sales
        $total_sales_income = $sales->sum('total_price');
        $total_sales_weight = $sales->sum('total_weight');
        
        // Total cost and weight from transactions
        $total_transaction_cost = $transactions->sum('total_price');
        $total_transaction_weight = $formattedTransactions->sum('total_weight');
        
        // Profit or Loss calculation
        $profit_or_loss = $total_sales_income - $total_transaction_cost;

        return response()->json([
            'success' => true,
            'message' => 'Sales and transactions retrieved successfully',
            'data' => [
                'transactions' => $formattedTransactions,
                'profit_or_loss' => 'Rp.' . number_format($profit_or_loss, 0, ',', '.'),
                'total_sales_income' => 'Rp.' . number_format($total_sales_income, 0, ',', '.'),
                'total_sales_weight' => number_format($total_sales_weight, 0, ',', '.') . ' kg',
                'total_transaction_cost' => 'Rp.' . number_format($total_transaction_cost, 0, ',', '.'),
                'total_transaction_weight' => number_format($total_transaction_weight, 0, ',', '.') . ' kg'
                ]
        ]);
    }
}
