<?php

namespace Paws\Controller;

use Symfony\Component\HttpFoundation\Request;
use Silex\ControllerProviderInterface;
use Paws\Application;

/**
 * Provides URL route controller
 *
 * @package Paws
 */
class Routing implements ControllerProviderInterface
{

    /**
     * {inheritdoc}
     */
    public function connect(\Silex\Application $app)
    {
        $ctl = $app['controllers_factory'];

        $ctl->get('', [$this, 'home'])
            ->before([$this, 'before'])
            ->bind('home');

        $ctl->match('/login', [$this, 'getLogin'])
            ->method('GET')
            ->before([$this, 'before'])
            ->bind('login');

        $ctl->match('/login', [$this, 'postLogin'])
            ->method('POST')
            ->before([$this, 'before'])
            ->bind('postLogin');

        $ctl->match("/users/{id}/edit", array($this, 'useredit'))
            ->before(array($this, 'before'))
            ->assert('id', '\d*')
            ->method('GET|POST')
            ->bind('useredit');

        return $ctl;
    }

    public function home(Application $app)
    {
        return $app['render']->render('index.twig');
    }

    public function getLogin(Application $app)
    {
        $app['render']->addGlobal('title', 'Login');
        return $app['render']->render('login.twig');
    }

    public function postLogin(Application $app, Request $request)
    {
        $user = $app['session']->get('user');

        if (($user instanceof $app['config']['user']['class']) && !empty($user->getId())) {
            return $app->abort(400, 'Invalid request - the user is already logged in.');
        }

        switch ($request->get('action')) {
            case 'login':
                $app['user']->setUserName($request->get('username'));
                $app['user']->setPassword($request->get('password'));
                $app['user']->authenticate();

                if ($app['user']->getId()) {
                    $app['session']->getFlashBag()->set('success', 'Whatever.');
                } else {
                    // Authentication failed. Redirect to the login page.
                    return $this->getLogin($app, $request);
                }

                $app['logger']->info('User {username} logged in.', ['username' => $user->getUserName()]);
                return $app->redirect(path('/user/' . $app['user']->getId()));

            default:
                // Let's not disclose any internal information.
                return $app->abort(400, 'Invalid request');
        }
    }


    public function before()
    {

    }
}
