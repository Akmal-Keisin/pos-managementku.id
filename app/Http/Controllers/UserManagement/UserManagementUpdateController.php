<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserManagement\UserManagementUpdateRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserManagementUpdateController extends Controller
{
	/**
	 * Update the specified user in storage.
	 */
	public function __invoke(UserManagementUpdateRequest $request, User $user)
	{
		try {
			$data = [
				'name' => $request->name,
				'username' => $request->username,
				'role' => $request->role,
			];

			// Only update password if provided
			if ($request->filled('password')) {
				$data['password'] = Hash::make($request->password);
			}

			$user->update($data);

			return redirect()
				->route('user-management.index')
				->with('success', 'User updated successfully.');
		} catch (\Exception $e) {
			return redirect()
				->back()
				->withInput()
				->with('error', 'Failed to update user: ' . $e->getMessage());
		}
	}
}
