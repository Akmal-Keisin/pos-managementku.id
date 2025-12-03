<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserManagement\UserManagementStoreRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserManagementStoreController extends Controller
{
	/**
	 * Store a newly created user in storage.
	 */
	public function __invoke(UserManagementStoreRequest $request)
	{
		try {
			User::create([
				'name' => $request->name,
				'username' => $request->username,
				'password' => Hash::make($request->password),
				'role' => $request->role,
			]);

			return redirect()
				->route('user-management.index')
				->with('success', 'User created successfully.');
		} catch (\Exception $e) {
			return redirect()
				->back()
				->withInput()
				->with('error', 'Failed to create user: ' . $e->getMessage());
		}
	}
}
