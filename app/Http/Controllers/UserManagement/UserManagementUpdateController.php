<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserManagement\UserManagementUpdateRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

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
            Log::error('User update failed', [
                'user_id' => $user->id ?? null,
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => app()->environment('production') ? null : $e->getTraceAsString(),
            ]);
            return redirect()
                ->back()
                ->withInput()
                ->with('alert', [
                    'type' => 'error',
                    'message' => 'Failed to update user.',
                    'description' => $e->getMessage(),
                ]);
        }
    }
}
