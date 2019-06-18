<?php

namespace App\Services;

use App\User;

interface AuthServiceInterface
{
    /**
     * Registers user and sends activation email
     *
     * @param string $name
     * @param string $email
     * @param string $password
     *
     * @return User
     */
    public function register(string $name, string $email, string $password): User;

    /**
     * Activates used using activation code from email
     *
     * @param string $email
     * @param string $activationCode
     *
     * @return User
     */
    public function activate(string $email, string $activationCode): User;

    /**
     * Checks user credentials and returns JWT token
     *
     * @param string $email
     * @param string $password
     *
     * @return string
     */
    public function authenticate(string $email, string $password): string;

    /**
     * Sends reset password code to email
     *
     * @param string $email
     *
     * @return User
     */
    public function reset(string $email): User;

    /**
     * Checks reset code from email and sets new password for user
     *
     * @param string $email
     * @param string $resetCode
     * @param string $newPassword
     *
     * @return User
     */
    public function change(string $email, string $resetCode, string $newPassword): User;
}