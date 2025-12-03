<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateSuperAdmin extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'create:super-admin';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a super admin user';

	/**
	 * Execute the console command.
	 */
	public function handle()
	{
		$this->info('Creating Super Admin User');
		$this->newLine();

		$name = $this->ask('Name');
		$username = $this->ask('Username');
		$password = $this->secret('Password');
		$passwordConfirmation = $this->secret('Confirm Password');

		// Validate input
		$validator = Validator::make([
			'name' => $name,
			'username' => $username,
			'password' => $password,
			'password_confirmation' => $passwordConfirmation,
		], [
			'name' => 'required|string|max:255',
			'username' => 'required|string|max:255|unique:users,username',
			'password' => 'required|min:8|confirmed',
		]);

		if ($validator->fails()) {
			$this->error('Validation failed:');
			foreach ($validator->errors()->all() as $error) {
				$this->error('- ' . $error);
			}
			return 1;
		}

		try {
			$user = User::create([
				'name' => $name,
				'username' => $username,
				'password' => Hash::make($password),
				'role' => 'super-admin',
			]);

			$this->newLine();
			$this->info('Super Admin user created successfully!');
			$this->table(
				['ID', 'Name', 'Username', 'Role'],
				[[$user->id, $user->name, $user->username, $user->role]]
			);

			return 0;
		} catch (\Exception $e) {
			$this->error('Failed to create super admin: ' . $e->getMessage());
			return 1;
		}
	}
}
