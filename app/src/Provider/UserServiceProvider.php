<?php

namespace Paws\Provider;

use Paws\User;
use Silex\Application;
use Silex\ServiceProviderInterface;

class UserServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['render'] = $app->share(function ($app) {
            $render = new User($app);
            return $render;
        });
    }

    public function boot(Application $app)
    {
    }
}
