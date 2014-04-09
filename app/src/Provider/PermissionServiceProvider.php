<?php
/**
 * @file
 * Contains PermissionServiceProvider.
 */

namespace Paws\Provider;

use Paws\Permission;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Provides service for user permissions.
 *
 * @package Paws\Provider
 */
class PermissionServiceProvider {
    public function register(Application $app)
    {
        $app['permission'] = $app->share(function ($app) {
            $permission = new Permission($app);
            return $permission;
        });
    }

    public function boot(Application $app)
    {
    }
} 
