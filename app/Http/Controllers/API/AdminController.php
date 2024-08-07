<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Schedule;
use App\Models\Transaction;
use App\Models\Sale;
use App\Models\Trash;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function index()
    {
        $admin = auth('sanctum')->user();
        $users = User::get()->load('roles');

        return response()->json([
            'success' => true,
            'admin' => $admin->name,
            'data' => $users
        ]);
    }
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'confirm_password' => 'required|same:password',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'role' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'address' => $request->address,
            'phone' => $request->phone,
            'ccm' => $request->ccm,
            'house_hold' => $request->house_hold,
        ]);


        $user->assignRole($request->role);
        $user->getRoleNames();

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user,
        ]);
    }

    public function show($id)
    {
        $user = User::find($id);
        $user->load('roles');
        return response()->json([
            'data' => $user
        ]);
    }

    public function update(Request $request, $id)
    {
        if (!empty($request->password) || !empty($request->confirm_password)) {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255',
                'password' => 'string|min:8',
                'confirm_password' => 'same:password',
                'address' => 'required|string|max:255',
                'phone' => 'required|string|max:255',
                'role' => 'required|string|max:255',
            ]);
    
            if ($validator->fails()) {
                return response()->json($validator->errors());
            }
            $user = User::find($id);
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'address' => $request->address,
                'phone' => $request->phone,
                'ccm' => $request->ccm,
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
            ]);
    
            if ($validator->fails()) {
                return response()->json($validator->errors());
            }

            $user = User::find($id);
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'address' => $request->address,
                'phone' => $request->phone,
                'ccm' => $request->ccm,
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
            'data' => $user,
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
        })->with('roles')->get();
        
        return response()->json([
            'data' => $users
        ]);
    }
    
    public function drivers()
    {
        $users = User::whereHas('roles', function($query) {
            $query->where('name', 'driver');
        })->with('roles')->get();
        
        return response()->json([
            'data' => $users
        ]);
    }

    public function userTransactions($id)
    {
        $user = User::find($id);
        $transactions = Transaction::whereHas('users', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with(['users.roles'])->get();
    
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
            'data' => $formattedTransactions
        ]);
    }


    public function transactions()
    {
        $transactions = Transaction::all();
    
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

        $request->date = date('Y-m-d', strtotime($request->date));

        $total_price = 0;
        for ($i = 0; $i < count($request->type_trash); $i++) {
            $total_price += $request->price[$i] * $request->weight[$i];
        }

        $total_weight = 0;
        for ($i = 0; $i < count($request->weight); $i++) {
            $total_weight += $request->weight[$i];
        }

        $sale = Sale::create([
            'date' => $request->date,
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
        $sales = Sale::all();

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
            ], 422);
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
}
