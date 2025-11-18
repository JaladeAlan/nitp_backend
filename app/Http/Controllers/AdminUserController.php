<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);
        $users = User::orderBy('created_at', 'desc')->paginate($perPage);

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request)
    {
        DB::beginTransaction();
        try {
            $role = $request->filled('role') && $request->role === 'admin' ? User::ROLE_ADMIN : 'member';

            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => $role,
            ]);

            DB::commit();
            Log::info('Admin created user', ['admin' => auth('api')->id(), 'user' => $user->id]);

            return new UserResource($user);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User creation failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to create user'], 500);
        }
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, $id)
    {
        $user = User::findOrFail($id);

        DB::beginTransaction();
        try {
            if ($request->filled('name')) {
                $user->name = $request->name;
            }

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            if ($request->filled('role')) {
                $user->role = $request->role === 'admin' ? User::ROLE_ADMIN : 'member';
            }

            $user->save();
            DB::commit();

            Log::info('Admin updated user', ['admin' => auth('api')->id(), 'user' => $user->id]);
            return new UserResource($user);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User update failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to update user'], 500);
        }
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        Log::info('Admin deleted user', ['admin' => auth('api')->id(), 'user' => $id]);
        return response()->json(['success' => true, 'message' => 'User deleted']);
    }
}
