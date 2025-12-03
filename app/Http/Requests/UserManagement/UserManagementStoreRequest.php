<?php

namespace App\Http\Requests\UserManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserManagementStoreRequest extends FormRequest
{
	/**
	 * Determine if the user is authorized to make this request.
	 */
	public function authorize(): bool
	{
		$user = $this->user();

		// Super admin can create admin or cashier
		if ($user->role === 'super-admin') {
			return in_array($this->role, ['admin', 'cashier']);
		}

		// Admin can only create cashier
		if ($user->role === 'admin') {
			return $this->role === 'cashier';
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

		$roleOptions = $user->role === 'super-admin'
			? ['admin', 'cashier']
			: ['cashier'];

		return [
			'name' => ['required', 'string', 'max:255'],
			'username' => ['required', 'string', 'max:255', 'unique:users,username'],
			'role' => ['required', 'string', Rule::in($roleOptions)],
			'password' => ['required', 'string', 'min:8'],
			'confirm_password' => ['required', 'string', 'same:password'],
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
