<?php

namespace Paws\Provider;

use Paws\User;
use Paws\Role;
use Silex\Application;
use Silex\ServiceProviderInterface;

class UserServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['role'] = $app->share(function ($app) {
            $role = new Role($app);
            return $role;
        });

        $app['user'] = $app->share(function ($app) {
            $user = new User($app);
            return $user;
        });
    }

    public function boot(Application $app)
    {
    }
}
