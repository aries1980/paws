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

        $ctl->match("/logout", array($this, 'logout'))
            ->method('GET')
            ->bind('logout');

        $ctl->match("/user/{id}", array($this, 'userView'))
            ->before(array($this, 'before'))
            ->assert('id', '\d*')
            ->method('GET')
            ->bind('userView');

        $ctl->match("/user/{id}/edit", array($this, 'userEdit'))
            ->before(array($this, 'before'))
            ->assert('id', '\d*')
            ->method('GET|POST')
            ->bind('userEdit');

        return $ctl;
    }

    public function home(Application $app)
    {
        return $app['render']->render('index.twig');
    }

    /**
     * HTTP GET handler for the Login page.
     */
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

                $app['logger']->info('User {username} logged in.', ['username' => $app['user']->getUserName()]);
                return $app->redirect('/user/' . $app['user']->getId());

            default:
                // Let's not disclose any internal information.
                return $app->abort(400, 'Invalid request');
        }
    }

    /**
     * HTTP GET handler for the Logout page.
     */
    public function logout(Application $app)
    {
        $app['logger']->info('User {username} logged out.', ['{username}' => $app['user']->getUserName()]);
        $app['user']->logout();
        return $app->redirect('/login');
    }

    public function before(Request $request, Application $app)
    {
        $route = $request->get('_route');

        // Check if there's at least one 'root' user, and otherwise promote the current user.
        //$app['user']->checkForRoot();
        //$app['user']->isValidSession();
        // Most of the 'check if user is allowed' happens here: match the current route to the 'allowed' settings.
        if (!$app['user']->isValidSession() && !$app['user']->isAllowed($route)) {
            $app['session']->getFlashBag()->set('info', 'Please log in.');

            return $app->redirect('/login');
        } elseif (!$app['user']->isAllowed($route)) {
            $app['session']->getFlashBag()->set('error', 'You do not have the right privileges to view that page.');

            return $app->redirect('/');
        }
    }

    public function userView(Application $app, Request $request)
    {
        $user = $app['user.factory'];
        $user->setId($request->get('id'))->retrieve();

        $app['render']->addGlobal('title', 'User profile of ' . $user->getUserName());
        return $app['render']->render('login.twig');
    }

    public function userEdit(Application $app, Request $request)
    {

    }
}
