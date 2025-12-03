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
    protected $signature = 'create:super-admin {--default : Use default credentials}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a super admin user';

    /**
     * Default credentials for super admin.
     */
    private const DEFAULT_NAME = 'Super Admin';
    private const DEFAULT_USERNAME = 'superadmin';
    private const DEFAULT_PASSWORD = 'password';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating Super Admin User');
        $this->newLine();

        // Check if default flag is set
        $useDefault = $this->option('default');

        if ($useDefault) {
            $this->warn('Using default credentials:');
            $this->line('Name: ' . self::DEFAULT_NAME);
            $this->line('Username: ' . self::DEFAULT_USERNAME);
            $this->line('Password: ' . self::DEFAULT_PASSWORD);
            $this->newLine();

            if (!$this->confirm('Do you want to proceed with default credentials?', true)) {
                $this->info('Operation cancelled.');
                return 0;
            }

            $name = self::DEFAULT_NAME;
            $username = self::DEFAULT_USERNAME;
            $password = self::DEFAULT_PASSWORD;
            $passwordConfirmation = self::DEFAULT_PASSWORD;
        } else {
            $name = $this->ask('Name');
            $username = $this->ask('Username');
            $password = $this->secret('Password');
            $passwordConfirmation = $this->secret('Confirm Password');
        }

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

            if ($useDefault) {
                $this->newLine();
                $this->warn('⚠️  IMPORTANT: You are using default credentials!');
                $this->warn('For security reasons, please change the password after first login.');
            }

            $this->newLine();
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
