<?php

namespace Tests\Unit;

use App\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * @test
     */
    public function testCanRegister()
    {
        Mail::fake();

        $this->post('/auth/register', [
            'name' => 'Bolson',
            'email' => 'darion@erdman.com',
            'password' => 'passwordcreated'
        ])
            ->seeJson([
                'active' => false, 'email' => 'darion@erdman.com', 'id' => 1, 'name' => 'Bolson'
            ])
            ->seeJsonStructure([
                'active', 'created_at', 'email', 'id', 'name', 'updated_at'
            ]);;
    }

    /**
     * @test
     */
    public function testCannotRegisterNoParametersPassed()
    {
        Mail::fake();

        $this->post('/auth/register', [])
            ->seeJson([
                'name' => [
                    'The name field is required.'
                ],
                'email' => [
                    'The email field is required.'
                ],
                'password' => [
                    'The password field is required.'
                ]
            ]);
    }

    /**
     * @test
     */
    public function testCanActivate()
    {
        $user = new User();
        $user->name = 'TestUser';
        $user->email = 'TestUser@somedomain.com';
        $user->password = Hash::make('somepassword');
        $activationCode = 'some1234';
        $user->activationCode = Hash::make($activationCode);
        $user->active = false;
        $user->save();

        $this->post('/auth/activate', [
            'email' => 'TestUser@somedomain.com',
            'activation_code' => $activationCode
        ])
            ->seeJson([
                'active' => true, 'email' => 'TestUser@somedomain.com', 'id' => 1, 'name' => 'TestUser'
            ])
            ->seeJsonStructure([
                'active', 'created_at', 'email', 'id', 'name', 'updated_at'
            ]);
    }

    /**
     * @test
     */
    public function testCannotActivateNoParametersPassed()
    {
        $user = new User();
        $user->name = 'TestUser';
        $user->email = 'TestUser@somedomain.com';
        $user->password = Hash::make('somepassword');
        $activationCode = '1234';
        $user->activationCode = Hash::make($activationCode);
        $user->active = false;
        $user->save();

        $this->post('/auth/activate', [])
            ->seeJson([
                'email' => [
                    'The email field is required.'
                ],
                'activation_code' => [
                    'The activation code field is required.'
                ],
            ]);;
    }

    /**
     * @test
     */
    public function testCanAuthenticate()
    {
        $user = new User();
        $user->name = 'TestUser';
        $user->email = 'TestUser@somedomain.com';
        $password = 'somepassword';
        $user->password = Hash::make($password);
        $user->active = true;
        $user->save();

        $this->post('/auth/login', [
            'email' => 'TestUser@somedomain.com',
            'password' => 'somepassword'
        ])
            ->seeJsonStructure([
                'token'
            ]);
    }

    /**
     * @test
     */
    public function testCannotAuthenticateNoParametersPassed()
    {
        $user = new User();
        $user->name = 'TestUser';
        $user->email = 'TestUser@somedomain.com';
        $password = 'somepassword';
        $user->password = Hash::make($password);
        $user->active = true;
        $user->save();

        $this->post('/auth/login', [])
            ->seeJson([
                'email' => [
                    'The email field is required.'
                ],
                'password' => [
                    'The password field is required.'
                ]
            ]);
    }

    /**
     * @test
     */
    public function testCanReset()
    {
        Mail::fake();

        $user = new User();
        $user->name = 'TestUser';
        $user->email = 'TestUser@somedomain.com';
        $password = 'somepassword';
        $user->password = Hash::make($password);
        $user->active = true;
        $user->save();

        $this->post('/auth/reset', [
            'email' => 'TestUser@somedomain.com',
        ])
            ->seeJsonEquals([
                'success' => true
            ]);
    }

    /**
     * @test
     */
    public function testCannotResetNoParametersPassed()
    {
        $user = new User();
        $user->name = 'TestUser';
        $user->email = 'TestUser@somedomain.com';
        $password = 'somepassword';
        $user->password = Hash::make($password);
        $user->active = true;
        $user->save();

        $this->post('/auth/reset', [])
            ->seeJson([
                'email' => [
                    'The email field is required.'
                ],
            ]);
    }

    /**
     * @test
     */
    public function testCanChange()
    {
        $user = new User();
        $user->name = 'TestUser';
        $user->email = 'TestUser@somedomain.com';
        $password = 'somepassword';
        $user->password = Hash::make($password);
        $user->active = true;
        $resetCode = 'someresetcode';
        $user->resetCode = Hash::make($resetCode);
        $user->resetCodeExpiration = '2100-01-01 00:00:00';
        $user->save();

        $this->post('/auth/change', [
            'email' => 'TestUser@somedomain.com',
            'reset_code' => 'someresetcode',
            'new_password' => 'newpassword',
        ])->seeJson([
            'active' => true, 'email' => 'TestUser@somedomain.com', 'id' => 1, 'name' => 'TestUser'
        ])
            ->seeJsonStructure([
                'active', 'created_at', 'email', 'id', 'name', 'updated_at'
            ]);
    }

    /**
     * @test
     */
    public function testCannotChange()
    {
        $user = new User();
        $user->name = 'TestUser';
        $user->email = 'TestUser@somedomain.com';
        $password = 'somepassword';
        $user->password = Hash::make($password);
        $user->active = true;
        $resetCode = 'someresetcode';
        $user->resetCode = Hash::make($resetCode);
        $user->resetCodeExpiration = '2100-01-01 00:00:00';
        $user->save();

        $this->post('/auth/change', [])
            ->seeJson([
                'email' => [
                    'The email field is required.'
                ],
                'reset_code' => [
                    'The reset code field is required.'
                ],
                'new_password' => [
                    'The new password field is required.'
                ],
            ]);
    }
}
