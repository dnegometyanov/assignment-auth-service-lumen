<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class AuthController extends BaseController
{
    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    private $request;

    /**
     * @var AuthService
     */
    private $authService;

    /**
     * Create a new controller instance.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @param AuthService $authService
     */
    public function __construct(Request $request, AuthService $authService)
    {
        $this->request = $request;
        $this->authService = $authService;
    }

    /**
     * Authenticate a user and return the token if the provided credentials are correct.
     *
     * @return mixed
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate()
    {
        $this->validate($this->request, [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $email = $this->request->input('email');
        $password = $this->request->input('password');

        try {
            $token = $this->authService->authenticate($email, $password);

            return response()->json([
                'token' => $token,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function register()
    {
        $this->validate($this->request, [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $name = $this->request->input('name');
        $email = $this->request->input('email');
        $password = $this->request->input('password');

        try {
            $user = $this->authService->register($name, $email, $password);

            return response()->json($user, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function activate()
    {
        $this->validate($this->request, [
            'email' => 'required|email',
            'activation_code' => 'required'
        ]);

        $email = $this->request->input('email');
        $activationCode = $this->request->input('activation_code');

        try {
            $user = $this->authService->activate($email, $activationCode);

            return response()->json($user, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function reset()
    {
        $this->validate($this->request, [
            'email' => 'required|email',
        ]);

        $email = $this->request->input('email');

        try {
            $user = $this->authService->reset($email);

            return response()->json($user, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function change()
    {
        $this->validate($this->request, [
            'email' => 'required|email',
            'reset_code' => 'required',
            'new_password' => 'required',
        ]);

        $email = $this->request->input('email');
        $resetCode = $this->request->input('reset_code');
        $newPassword = $this->request->input('new_password');

        try {
            $user = $this->authService->change($email, $resetCode, $newPassword);

            return response()->json($user, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

}