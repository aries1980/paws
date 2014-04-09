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

        $app['user.class'] = $app['config']['user']['class'];
        $app['user.factory'] = function () use ($app) {
            return new $app['user.class']($app);
        };
    }

    public function boot(Application $app)
    {
        $app['user'] = $app['user.factory'];
    }
}
