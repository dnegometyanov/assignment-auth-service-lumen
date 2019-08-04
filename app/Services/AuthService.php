<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\IllegalArgumentException;
use App\Mail\Activation;
use App\Mail\Reset;
use App\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthService implements AuthServiceInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws IllegalArgumentException
     */
    public function register(string $name, string $email, string $password): User
    {
        $validator = Validator::make(
            [
                'name'     => $name,
                'email'    => $email,
                'password' => $password,
            ],
            [
                'name'     => AuthRules::getNameValidationRule(),
                'email'    => AuthRules::getEmailValidationRule(),
                'password' => AuthRules::getPasswordValidationRule(),
            ]
        );

        if ($validator->fails()) {
            throw new IllegalArgumentException(implode(' ', $validator->errors()->all()));
        }

        $existingUser = User::where('email', $email)->first();

        if (null !== $existingUser) {
            throw new IllegalArgumentException(sprintf('User with email %s already exists.', $email));
        }

        $user           = new User();
        $user->name     = $name;
        $user->email    = $email;
        $user->password = Hash::make($password);
        $user->active   = false;

        $activationCode       = $this->generatePassword();
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
     * {@inheritdoc}
     *
     * @throws IllegalArgumentException
     */
    public function activate(string $email, string $activationCode): User
    {
        $validator = Validator::make(
            [
                'email'           => $email,
                'activation_code' => $activationCode,
            ],
            [
                'email'           => AuthRules::getEmailValidationRule(),
                'activation_code' => AuthRules::getActivationCodeValidationRule(),
            ]
        );

        if ($validator->fails()) {
            throw new IllegalArgumentException(implode(' ', $validator->errors()->all()));
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new IllegalArgumentException(sprintf('User with email %s does not exist.', $email));
        }

        if (true === $user->active) {
            throw new IllegalArgumentException('User already activated.');
        }

        if (false === Hash::check($activationCode, $user->activationCode)) {
            throw new IllegalArgumentException('Activation code is incorrect.');
        }

        $user->active = true;

        $user->save();

        return $user;
    }

    /**
     * {@inheritdoc}
     *
     * @throws IllegalArgumentException
     */
    public function authenticate(string $email, string $password): string
    {
        $validator = Validator::make(
            [
                'email'    => $email,
                'password' => $password,
            ],
            [
                'email'    => AuthRules::getEmailValidationRule(),
                'password' => AuthRules::getPasswordValidationRule(),
            ]
        );

        if ($validator->fails()) {
            throw new IllegalArgumentException(implode(' ', $validator->errors()->all()));
        }

        $user = User::where('email', $email)->first();

        if (true == empty($user)) {
            throw new IllegalArgumentException(sprintf('User with email %s does not exist.', $email));
        }

        if (true !== $user->active) {
            throw new IllegalArgumentException(sprintf('User with email %s is not active.', $email));
        }

        if (false === Hash::check($password, $user->password)) {
            throw new IllegalArgumentException(sprintf('Password is incorrect.'));
        }

        return $this->jwt($user);
    }

    /**
     * {@inheritdoc}
     *
     * @throws IllegalArgumentException
     */
    public function reset(string $email): User
    {
        $validator = Validator::make(
            [
                'email' => $email,
            ],
            [
                'email' => AuthRules::getEmailValidationRule(),
            ]
        );

        if ($validator->fails()) {
            throw new IllegalArgumentException(implode(' ', $validator->errors()->all()));
        }

        // Find the user by email
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new IllegalArgumentException(sprintf('User with email %s does not exist.', $email));
        }

        $resetCode       = $this->generatePassword();
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
     * {@inheritdoc}
     *
     * @throws IllegalArgumentException
     */
    public function change(string $email, string $resetCode, string $newPassword): User
    {
        $validator = Validator::make(
            [
                'email'       => $email,
                'resetCode'   => $resetCode,
                'newPassword' => $newPassword,
            ],
            [
                'email'       => AuthRules::getEmailValidationRule(),
                'resetCode'   => AuthRules::getResetCodeValidationRule(),
                'newPassword' => AuthRules::getPasswordValidationRule(),
            ]
        );

        if ($validator->fails()) {
            throw new IllegalArgumentException(implode(' ', $validator->errors()->all()));
        }

        // Find the user by email
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new IllegalArgumentException(sprintf('User with email %s does not exist.', $email));
        }

        if (false === Hash::check($resetCode, $user->resetCode)) {
            throw new IllegalArgumentException('Reset code is incorrect.');
        }

        if (Date::create('now') > Date::createFromFormat('Y-m-d H:i:s', $user->resetCodeExpiration)) {
            throw new IllegalArgumentException('Reset code is expired.');
        }

        $user->password = Hash::make($newPassword);

        $user->save();

        return $user;
    }

    /**
     * Helper method to create a JWT token.
     *
     * @param \App\User $user
     *
     * @return string
     */
    protected function jwt(User $user): string
    {
        $payload = [
            'iss' => env('JWT_ISSUER'), // Issuer of the token
            'sub' => $user->id, // Subject of the token
            'iat' => time(), // Time when JWT was issued.
            'exp' => time() + env('JWT_TOKEN_EXPIRATION_PERIOD'), // Expiration time
        ];

        return JWT::encode($payload, env('JWT_SECRET'));
    }

    /**
     * Generates random password of allowed symbols and range of length.
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function generatePassword(): string
    {
        $count  = mb_strlen(AuthRules::PASSWORD_ALLOWED_CHARS);
        $length = random_int(AuthRules::PASSWORD_MAX_LENGTH, AuthRules::PASSWORD_MAX_LENGTH);

        for ($i = 0, $password = ''; $i < $length; $i++) {
            $index = random_int(0, $count - 1);
            $password .= mb_substr(AuthRules::PASSWORD_ALLOWED_CHARS, $index, 1);
        }

        return $password;
    }
}
