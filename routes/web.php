<?php

$router->post(
    'auth/login',
    [
        'uses' => 'AuthController@authenticate'
    ]
);

$router->post(
    'auth/register',
    [
        'uses' => 'AuthController@register'
    ]
);

$router->post(
    'auth/activate',
    [
        'uses' => 'AuthController@activate'
    ]
);

$router->post(
    'auth/reset',
    [
        'uses' => 'AuthController@reset'
    ]
);

$router->post(
    'auth/change',
    [
        'uses' => 'AuthController@change'
    ]
);

$router->group(
    ['middleware' => 'jwt.auth'],
    function () use ($router) {
        $router->get('users', function () {
            $users = \App\User::all();
            return response()->json($users);
        });
    }
);