<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementViewController extends Controller
{
	/**
	 * Display a listing of users.
	 */
	public function index(Request $request): Response
	{
		$query = User::query();

		// Filter users based on current user's role
		$user = $request->user();
		if ($user->role === 'admin') {
			// Admin can only see cashiers
			$query->where('role', 'cashier');
		}
		// Super admin sees all users (no filter needed)

		// Search functionality
		if ($request->has('search') && $request->search) {
			$search = $request->search;
			$query->where(function ($q) use ($search) {
				$q->where('name', 'like', "%{$search}%")
					->orWhere('username', 'like', "%{$search}%");
			});
		}

		$users = $query->latest()->paginate(10);

		return Inertia::render('user-management/Index', [
			'users' => $users,
			'filters' => $request->only(['search']),
		]);
	}

	/**
	 * Show the form for creating a new user.
	 */
	public function create(): Response
	{
		$user = Auth::user();

		$availableRoles = $user->role === 'super-admin'
			? ['admin', 'cashier']
			: ['cashier'];

		return Inertia::render('user-management/Create', [
			'availableRoles' => $availableRoles,
		]);
	}

	/**
	 * Show the form for editing the specified user.
	 */
	public function edit(User $user): Response
	{
		$currentUser = Auth::user();

		// Check if admin is trying to edit non-cashier
		if ($currentUser->role === 'admin' && $user->role !== 'cashier') {
			abort(403, 'Unauthorized action.');
		}

		$availableRoles = $currentUser->role === 'super-admin'
			? ['admin', 'cashier']
			: ['cashier'];

		return Inertia::render('user-management/Edit', [
			'user' => $user,
			'availableRoles' => $availableRoles,
		]);
	}
}
