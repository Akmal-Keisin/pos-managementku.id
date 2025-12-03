<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserManagementDeleteController extends Controller
{
    /**
     * Remove the specified user from storage.
     */
    public function __invoke(Request $request, User $user)
    {
        try {
            // Check if admin is trying to delete non-cashier
            if ($request->user()->role === 'admin' && $user->role !== 'cashier') {
                abort(403, 'Unauthorized action.');
            }

            // Prevent deleting yourself
            if ($request->user()->id === $user->id) {
                return redirect()
                    ->back()
                    ->with('alert', [
                        'type' => 'error',
                        'message' => 'You cannot delete your own account.',
                    ]);
            }
            $user->delete();

            return redirect()
                ->route('user-management.index')
                ->with('alert', [
                    'type' => 'success',
                    'message' => 'User deleted successfully.',
                ]);
        } catch (\Exception $e) {
            Log::error('User delete failed', [
                'user_id' => $user->id ?? null,
                'message' => $e->getMessage(),
            ]);
            return redirect()
                ->back()
                ->with('alert', [
                    'type' => 'error',
                    'message' => 'Failed to delete user.',
                    'description' => $e->getMessage(),
                ]);
        }
    }
}
