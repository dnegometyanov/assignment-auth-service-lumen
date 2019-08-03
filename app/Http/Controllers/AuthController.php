<?php

namespace App\Http\Controllers;

use App\Services\AuthRules;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class AuthController extends BaseController
{
    /**
     * @var AuthService
     */
    private $authService;

    /**
     * Create a new controller instance.
     *
     * @param AuthService $authService
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Authenticate a user and return the token if the provided credentials are correct.
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(Request $request): JsonResponse
    {
        $this->validate($request, [
            'email' => AuthRules::getEmailValidationRule(),
            'password' => AuthRules::getPasswordValidationRule(),
        ]);

        $email = $request->input('email');
        $password = $request->input('password');

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

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function register(Request $request): JsonResponse
    {
        $this->validate($request, [
            'name' => AuthRules::getNameValidationRule(),
            'email' => AuthRules::getEmailValidationRule(),
            'password' => AuthRules::getPasswordValidationRule(),
        ]);

        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');

        try {
            $user = $this->authService->register($name, $email, $password);

            return response()->json($user, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function activate(Request $request): JsonResponse
    {
        $this->validate($request, [
            'email' => AuthRules::getEmailValidationRule(),
            'activation_code' => AuthRules::getActivationCodeValidationRule(),
        ]);

        $email = $request->input('email');
        $activationCode = $request->input('activation_code');

        try {
            $user = $this->authService->activate($email, $activationCode);

            return response()->json($user, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function reset(Request $request): JsonResponse
    {
        $this->validate($request, [
            'email' => AuthRules::getEmailValidationRule(),
        ]);

        $email = $request->input('email');

        try {
            $user = $this->authService->reset($email);

            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function change(Request $request): JsonResponse
    {
        $this->validate($request, [
            'email' => AuthRules::getEmailValidationRule(),
            'reset_code' => AuthRules::getResetCodeValidationRule(),
            'new_password' => AuthRules::getPasswordValidationRule(),
        ]);

        $email = $request->input('email');
        $resetCode = $request->input('reset_code');
        $newPassword = $request->input('new_password');

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
