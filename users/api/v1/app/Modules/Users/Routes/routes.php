<?php
/**
 * This Routes holds all the individual route for the Users
 *
 * PHP version 5.6
 *
 * @category  Routes
 * @package   Routes
 * @author    Tanmaya <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
use App\Middlewares\ValidateJWTToken as ValidateJWT;
use App\Modules\Users\Controllers\UsersController as Users;

$container = $app->getContainer();

$app->group(
    '/user', function () use ($app) {
        $app->post('/login', Users::class . ':login');
    }
);

//Privileges
$app->group(
    '/privileges', function () use ($app) {
        $app->get('', Users::class . ':getPrivileges');
    }
)->add(new ValidateJWT($container));

// User Roles

$app->group(
    '/user-roles', function () use ($app) {
        $app->get('', Users::class . ':getUserRoles');
        $app->get('/{id}', Users::class . ':getUserRoles');
        $app->post('', Users::class . ':saveUserRole');
        $app->post('/{id}', Users::class . ':updateUserRole');
        $app->delete('/{id}', Users::class . ':deleteUserRole');
        $app->post('/{id}/privileges', Users::class . ':saveUserRolePrivileges');
    }
)->add(new ValidateJWT($container));

// Users
$app->group(
    '/users', function () use ($app) {
        $app->get('', Users::class . ':getUsers');
        $app->get('/security-questions', Users::class . ':getSecurityQuestions');
        $app->get('/{id}', Users::class . ':getUsers');
        $app->post('', Users::class . ':saveUser');
        $app->delete('/{id}', Users::class . ':deleteUser');
        $app->post('/{id}/privileges', Users::class . ':saveUserPrivileges');
        $app->post('/{id}/types', Users::class . ':saveUserTypes');
        $app->post('/verify', Users::class . ':verifyPassword');
        $app->post('/{id}', Users::class . ':updateUser');
    }
)->add(new ValidateJWT($container));

// Users
$app->group(
    '/update-date', function () use ($app) {
        $app->post('', Users::class . ':updatePasswordReset');
    }
)->add(new ValidateJWT($container));

// Email Validate
$app->group(
    '/password', function () use ($app) {
        $app->post('/valide-email', Users::class . ':validateEmail');
    }
);

// Reset Password Section
$app->group(
    '/password', function () use ($app) {
        $app->post('/validate', Users::class . ':validateDetails');
        $app->post('/update', Users::class . ':updatePassword');
        $app->post('/reset', Users::class . ':resetPassword');
    }
)->add(new ValidateJWT($container));
