# Cordial test task

## Create authentication REST service.
### Service methods:
 - Registration (with data validation, and confirmation email containing activation code)
 - Activation (using code from email)
 - Authentication (by login/password)
 - Password reset (send reset link with code to email, link expires in 1 hour)
 - Password change (using code from email)

### Requirements:
 - Use Lumen framework
 - Service must be implemented as standalone class with list of public methods (i.e. public function auth($login, $password))
 - Service methods must be available as REST endpoints mapped to service class methods (REST controller + routes)
 - Service class methods must be covered by UnitTest 
 
### My implementation notes
 - It uses JWT auth, jwt helper library itself is third party
 - For simplification purposes I've used synchronous mailing
 - For simplification purposes I've stored activation / reset code data in User model
 - I've used this article as a starter https://medium.com/tech-tajawal/jwt-authentication-for-lumen-5-6-2376fd38d454
 
### Usage
 - Create 2 databases for project and for tests (for example `cordial` and `cordial_test`)
 - Copy `.env.example` to `.env` and `.env.testing.example` to `.env.testing` and set up parameters
 - Migrate both of them `php artisan migrate` and `php artisan migrate --env="testing"`
 - Use `./vendor/bin/phpunit` to run unit tests (`AuthService` is covered)
 - Exported Postman collection is in `Cordial-Auth-App.postman_collection.json`