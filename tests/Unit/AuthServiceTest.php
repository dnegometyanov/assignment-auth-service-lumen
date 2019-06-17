<?php

namespace Tests\Unit;

use App\Services\AuthService;
use App\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * @test
     */
    public function testCanRegister()
    {
        $name = 'TestUser';
        $email = 'TestUser@somedomain.com';
        $password = 'somepassword';

        $authService = new AuthService();

        $registeredUser = $authService->register($name, $email, $password);

        $this->assertEquals($name, $registeredUser->name);
        $this->assertEquals($email, $registeredUser->email);
        $this->assertEquals(false, $registeredUser->active);
        $this->assertTrue(Hash::check($password, $registeredUser->password));
        $this->assertNotNull($registeredUser->activationCode);
    }

    /**
     * @test
     */
    public function testCanonRegisterWithExistingEmail()
    {
        $this->expectExceptionMessage('User with email TestUser@somedomain.com already exists.');

        $name = 'TestUser';
        $name2 = 'TestUser2';
        $email = 'TestUser@somedomain.com';
        $password = 'somepassword';
        $password2 = 'somepassword2';

        $authService = new AuthService();

        $authService->register($name, $email, $password);
        $authService->register($name2, $email, $password2);
    }

    /**
     * @test
     */
    public function testCanActivate()
    {
        $user = new User();
        $user->name = 'TestUser';
        $user->email = 'TestUser@somedomain.com';
        $user->password = password_hash('somepassword', PASSWORD_BCRYPT);
        $activationCode = '1234';
        $user->activationCode = password_hash($activationCode, PASSWORD_BCRYPT);
        $user->active = false;
        $user->save();

        $email = 'TestUser@somedomain.com';

        $authService = new AuthService();

        $activatedUser = $authService->activate($email, $activationCode);

        $this->assertEquals(true, $activatedUser->active);
    }

    /**
     * @test
     */
    public function testCannotActivateWhenEmailNotExist()
    {
        $this->expectExceptionMessage('User with email TestUserNotExist@somedomain.com does not exist.');

        $user = new User();
        $user->name = 'TestUser';
        $user->email = 'TestUser@somedomain.com';
        $user->password = password_hash('somepassword', PASSWORD_BCRYPT);
        $activationCode = '1234';
        $user->activationCode = password_hash($activationCode, PASSWORD_BCRYPT);
        $user->active = false;
        $user->save();

        $email = 'TestUserNotExist@somedomain.com';

        $authService = new AuthService();

        $authService->activate($email, $activationCode);
    }

    /**
     * @test
     */
    public function testCannotActivateActiveUser()
    {
        $this->expectExceptionMessage('User already activated.');

        $user = new User();
        $user->name = 'TestUser';
        $user->email = 'TestUser@somedomain.com';
        $user->password = password_hash('somepassword', PASSWORD_BCRYPT);
        $activationCode = '1234';
        $user->activationCode = password_hash($activationCode, PASSWORD_BCRYPT);
        $user->active = true;
        $user->save();

        $email = 'TestUser@somedomain.com';

        $authService = new AuthService();

        $authService->activate($email, $activationCode);
    }

    /**
     * @test
     */
    public function testCannotActivateIncorrectCode()
    {
        $this->expectExceptionMessage('Activation code is incorrect.');

        $user = new User();
        $user->name = 'TestUser';
        $user->email = 'TestUser@somedomain.com';
        $user->password = password_hash('somepassword', PASSWORD_BCRYPT);
        $activationCode = '1234';
        $user->activationCode = password_hash($activationCode, PASSWORD_BCRYPT);
        $user->active = false;
        $user->save();

        $email = 'TestUser@somedomain.com';

        $authService = new AuthService();

        $authService->activate($email, '4321');
    }
}