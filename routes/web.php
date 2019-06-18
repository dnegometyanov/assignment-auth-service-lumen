<?php

// Login uses email / password
$router->post(
    'auth/login',
    [
        'uses' => 'AuthController@authenticate'
    ]
);

// Activate should be available without checking token or credentials, it can register user with non existing email
$router->post(
    'auth/register',
    [
        'uses' => 'AuthController@register'
    ]
);

// Activate should be available without checking token or credentials, it uses activation code from email
$router->post(
    'auth/activate',
    [
        'uses' => 'AuthController@activate'
    ]
);

// Reset should be available without checking token or credentials
$router->post(
    'auth/reset',
    [
        'uses' => 'AuthController@reset'
    ]
);

// Change should be available without checking token or credentials, it uses reset code from email
$router->post(
    'auth/change',
    [
        'uses' => 'AuthController@change'
    ]
);

// This route is not a part of auth, and is here for testing, in live auth app it should be removed
// Test route protected by token
// Token is passed as https parameter, but not as bearer header
$router->group(
    ['middleware' => 'jwt.auth'],
    function () use ($router) {
        $router->get('users', function () {
            $users = \App\User::all();
            return response()->json($users);
        });
    }
);