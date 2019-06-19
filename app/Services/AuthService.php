<?php

namespace App\Services;

use App\Mail\Activation;
use App\Mail\Reset;
use App\User;
use Illuminate\Support\Facades\Date;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthService implements AuthServiceInterface
{
    /**
     * @inheritdoc
     *
     * @throws \Exception
     */
    public function register(string $name, string $email, string $password): User
    {
        $existingUser = User::where('email', $email)->first();

        if (null !== $existingUser) {
            throw new \Exception(sprintf('User with email %s already exists.', $email));
        }

        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->active = false;

        $activationCode = rand(1000, 9999);
        $user->activationCode = Hash::make($activationCode);

        // Test task implementation uses synchronous mailer for simplification
        Mail::to($user->email)
            ->send(
                new Activation(
                    $user->email,
                    $activationCode)
            );

        $user->save();

        return $user;
    }

    /**
     * @inheritdoc
     *
     * @throws \Exception
     */
    public function activate(string $email, string $activationCode): User
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new \Exception(sprintf('User with email %s does not exist.', $email));
        }

        if (true === $user->active) {
            throw new \Exception('User already activated.');
        }

        if (false === Hash::check($activationCode, $user->activationCode)) {
            throw new \Exception('Activation code is incorrect.');
        }

        $user->active = true;

        $user->save();

        return $user;
    }

    /**
     * @inheritdoc
     *
     * @throws \Exception
     */
    public function authenticate(string $email, string $password): string
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new \Exception(sprintf('User with email %s does not exist.', $email));
        }

        if (false === Hash::check($password, $user->password)) {
            throw new \Exception(sprintf('Password is incorrect.', $email));
        }

        return $this->jwt($user);
    }

    /**
     * @inheritdoc
     *
     * @throws \Exception
     */
    public function reset(string $email): User
    {
        // Find the user by email
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new \Exception(sprintf('User with email %s does not exist.', $email));
        }

        $resetCode = rand(100000, 999999);
        $user->resetCode = Hash::make($resetCode);

        $user->resetCodeExpiration = Date::now()
            ->addMinute(env('ACTIVATION_CODE_EXPIRATION_PERIOD_MINUTES'))
            ->format('Y-m-d H:i:s');

        // Test task implementation uses synchronous mailer for simplification
        Mail::to($user->email)
            ->send(
                new Reset(
                    $user->email,
                    $resetCode)
            );

        $user->save();

        return $user;
    }

    /**
     * @inheritdoc
     *
     * @throws \Exception
     */
    public function change(string $email, string $resetCode, string $newPassword): User
    {
        // Find the user by email
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new \Exception(sprintf('User with email %s does not exist.', $email));
        }

        if (false === Hash::check($resetCode, $user->resetCode)) {
            throw new \Exception('Reset code is incorrect.');
        }

        if (Date::create('now') > Date::createFromFormat('Y-m-d H:i:s', $user->resetCodeExpiration)) {
            throw new \Exception('Reset code is expired.');
        }

        $user->password = Hash::make($newPassword);

        $user->save();

        return $user;
    }

    /**
     * Helper method to create a JWT token.
     *
     * @param  \App\User $user
     *
     * @return string
     */
    protected function jwt(User $user): string
    {
        $payload = [
            'iss' => env('JWT_ISSUER'), // Issuer of the token
            'sub' => $user->id, // Subject of the token
            'iat' => time(), // Time when JWT was issued.
            'exp' => time() + env('JWT_TOKEN_EXPIRATION_PERIOD') // Expiration time
        ];

        return JWT::encode($payload, env('JWT_SECRET'));
    }
}
