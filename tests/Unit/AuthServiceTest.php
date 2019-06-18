<?php

namespace Tests\Unit;

use App\Mail\Activation;
use App\Mail\Reset;
use App\Services\AuthService;
use App\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use Firebase\JWT\JWT;

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

        Mail::fake();

        $registeredUser = $authService->register($name, $email, $password);

        $this->assertEquals($name, $registeredUser->name);
        $this->assertEquals($email, $registeredUser->email);
        $this->assertEquals(false, $registeredUser->active);
        $this->assertTrue(Hash::check($password, $registeredUser->password));
        $this->assertNotNull($registeredUser->activationCode);
        Mail::assertSent(Activation::class);
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

        Mail::fake();

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

    /**
     * @test
     */
    public function testCanAuthenticate()
    {
        $user = new User();
        $user->name = 'TestUser';
        $user->email = 'TestUser@somedomain.com';
        $password = 'somepassword';
        $user->password = password_hash($password, PASSWORD_BCRYPT);
        $user->active = true;
        $user->save();

        $email = 'TestUser@somedomain.com';

        $authService = new AuthService();

        $jwtToken = $authService->authenticate($email, $password);

        $this->assertNotEmpty($jwtToken);
        $decodedJwtToken = JWT::decode($jwtToken, env('JWT_SECRET'), array('HS256'));
        $this->assertEquals('Cordial', $decodedJwtToken->iss);
        $this->assertEquals($user->id, $decodedJwtToken->sub);
        $this->assertEquals(env('JWT_TOKEN_EXPIRATION_PERIOD'), $decodedJwtToken->exp - $decodedJwtToken->iat);
    }

    /**
     * @test
     */
    public function testCannotAuthenticateNotExistingUser()
    {
        $this->expectExceptionMessage('User with email TestUserNotExisting@somedomain.com does not exist.');

        $user = new User();
        $user->name = 'TestUser';
        $user->email = 'TestUser@somedomain.com';
        $password = 'somepassword';
        $user->password = password_hash($password, PASSWORD_BCRYPT);
        $user->active = true;
        $user->save();

        $authService = new AuthService();

        $authService->authenticate('TestUserNotExisting@somedomain.com', $password);
    }

    /**
     * @test
     */
    public function testCannotAuthenticateWithIncorrectPassword()
    {
        $this->expectExceptionMessage('Password is incorrect.');

        $user = new User();
        $user->name = 'TestUser';
        $user->email = 'TestUser@somedomain.com';
        $password = 'somepassword';
        $user->password = password_hash($password, PASSWORD_BCRYPT);
        $user->active = true;
        $user->save();

        $authService = new AuthService();

        $authService->authenticate('TestUser@somedomain.com', 'badpassword');
    }

    /**
     * @test
     */
    public function testCanReset()
    {
        $user = new User();
        $user->name = 'TestUser';
        $user->email = 'TestUser@somedomain.com';
        $password = 'somepassword';
        $user->password = password_hash($password, PASSWORD_BCRYPT);
        $user->active = true;
        $user->save();

        $email = 'TestUser@somedomain.com';

        $authService = new AuthService();

        Mail::fake();

        $user = $authService->reset($email);

        $this->assertNotEmpty($user->resetCodeExpiration);
        $this->assertNotEmpty($user->resetCode);
        Mail::assertSent(Reset::class);
    }

    /**
     * @test
     */
    public function testCannotResetNotExistingUser()
    {
        $this->expectExceptionMessage('User with email TestUserNotExisting@somedomain.com does not exist.');

        $user = new User();
        $user->name = 'TestUser';
        $user->email = 'TestUser@somedomain.com';
        $password = 'somepassword';
        $user->password = password_hash($password, PASSWORD_BCRYPT);
        $user->active = true;
        $user->save();

        $authService = new AuthService();

        Mail::fake();

        $authService->reset('TestUserNotExisting@somedomain.com');
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
        $user->password = password_hash($password, PASSWORD_BCRYPT);
        $user->active = true;
        $resetCode = 'someresetcode';
        $user->resetCode =  password_hash($resetCode, PASSWORD_BCRYPT);
        $user->resetCodeExpiration = '2100-01-01 00:00:00';
        $user->save();

        $authService = new AuthService();

        $newPassword ='newpassword';
        $user = $authService->change('TestUser@somedomain.com', $resetCode, $newPassword);

        $this->assertTrue(Hash::check($newPassword, $user->password));
    }

    /**
     * @test
     */
    public function testCannotChangeNotExistingUser()
    {
        $this->expectExceptionMessage('User with email TestUserNotExisting@somedomain.com does not exist.');

        $user = new User();
        $user->name = 'TestUser';
        $user->email = 'TestUser@somedomain.com';
        $password = 'somepassword';
        $user->password = password_hash($password, PASSWORD_BCRYPT);
        $user->active = true;
        $resetCode = 'someresetcode';
        $user->resetCode =  password_hash($resetCode, PASSWORD_BCRYPT);
        $user->resetCodeExpiration = '2100-01-01 00:00:00';
        $user->save();

        $authService = new AuthService();

        $newPassword ='newpassword';
        $user = $authService->change('TestUserNotExisting@somedomain.com', $resetCode, $newPassword);
    }

    /**
     * @test
     */
    public function testCannotChangenotResetCodeIsIncorrect()
    {
        $this->expectExceptionMessage('Reset code is incorrect.');

        $user = new User();
        $user->name = 'TestUser';
        $user->email = 'TestUser@somedomain.com';
        $password = 'somepassword';
        $user->password = password_hash($password, PASSWORD_BCRYPT);
        $user->active = true;
        $resetCode = 'someresetcode';
        $user->resetCode =  password_hash($resetCode, PASSWORD_BCRYPT);
        $user->resetCodeExpiration = '2100-01-01 00:00:00';
        $user->save();

        $authService = new AuthService();

        $newPassword ='newpassword';
        $user = $authService->change('TestUser@somedomain.com', 'incorrectresetcode', $newPassword);
    }

    /**
     * @test
     */
    public function testCannotChangenotResetCodeIsExpired()
    {
        $this->expectExceptionMessage('Reset code is expired.');

        $user = new User();
        $user->name = 'TestUser';
        $user->email = 'TestUser@somedomain.com';
        $password = 'somepassword';
        $user->password = password_hash($password, PASSWORD_BCRYPT);
        $user->active = true;
        $resetCode = 'someresetcode';
        $user->resetCode =  password_hash($resetCode, PASSWORD_BCRYPT);
        $user->resetCodeExpiration = '2000-01-01 00:00:00';
        $user->save();

        $authService = new AuthService();

        $newPassword ='newpassword';
        $user = $authService->change('TestUser@somedomain.com', $resetCode, $newPassword);
    }
}