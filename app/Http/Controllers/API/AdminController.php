<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function index()
    {
        $users = User::all()->load('roles');
        return response()->json([
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

    public function transactions()
    {
        $transactions = Transaction::all();
        return response()->json([
            'data' => $transactions
        ]);
    }
}
