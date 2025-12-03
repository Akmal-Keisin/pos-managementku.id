<?php

namespace App\Http\Requests\UserManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserManagementUpdateRequest extends FormRequest
{
	/**
	 * Determine if the user is authorized to make this request.
	 */
	public function authorize(): bool
	{
		$user = $this->user();
		$targetUser = $this->route('user');

		// Admin can only update cashiers
		if ($user->role === 'admin') {
			return $targetUser->role === 'cashier' && $this->role === 'cashier';
		}

		// Super admin can update admin or cashier
		if ($user->role === 'super-admin') {
			return in_array($this->role, ['admin', 'cashier']);
		}

		return false;
	}

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
	 */
	public function rules(): array
	{
		$user = $this->user();
		$targetUser = $this->route('user');

		$roleOptions = $user->role === 'super-admin'
			? ['admin', 'cashier']
			: ['cashier'];

		return [
			'name' => ['required', 'string', 'max:255'],
			'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($targetUser->id)],
			'role' => ['required', 'string', Rule::in($roleOptions)],
			'password' => ['nullable', 'string', 'min:8'],
			'confirm_password' => ['nullable', 'string', 'same:password', 'required_with:password'],
		];
	}

	/**
	 * Get custom attributes for validator errors.
	 *
	 * @return array<string, string>
	 */
	public function attributes(): array
	{
		return [
			'confirm_password' => 'password confirmation',
		];
	}
}
