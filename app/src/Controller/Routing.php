<?php

namespace Paws\Controller;

use Paws\Entity\User;
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

        $ctl->match('/signup', [$this, 'getSignup'])
            ->method('GET')
            ->before([$this, 'before'])
            ->bind('signup');

        $ctl->match('/signup', [$this, 'postSignup'])
            ->method('POST')
            ->before([$this, 'before'])
            ->bind('postSignup');

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

        if ($user instanceof $app['config']['user']['class']) {
            $id = $user->getId();
            if (!empty($id)) {
                 return $app->abort(400, 'Invalid request - the user is already logged in.');
            }
        }

        switch ($request->get('action')) {
            case 'login':
                $app['user']->setUserName($request->get('username'));
                $app['user']->setPassword($request->get('password'));
                $app['user']->authenticate();

                if ($app['user']->getId()) {
                    $app['session']->getFlashBag()->set('success', 'You successfully logged in.');
                } else {
                    // Authentication failed. Redirect to the login page.
                    return $this->getLogin($app, $request);
                }

                $app['logger']->info('User {username} logged in.', ['username' => $app['user']->getUserName()]);
                $subRequest = Request::create('/user/' . $app['user']->getId(), 'GET');
                return $app->handle($subRequest, \Symfony\Component\HttpKernel\HttpKernelInterface::SUB_REQUEST);

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

        if ($app['session']->has('user')) {
            $app['render']->addGlobal('user', $app['session']->get('user'));
        }
    }

    /**
     * HTTP GET handler for profile page of a given user.
     */
    public function userView(Application $app, Request $request)
    {
        $user = $app['user.factory'];
        $user->setId($request->get('id'))->retrieve();

        $app['render']->addGlobal('title', 'User profile of ' . $user->getUserName());
        $app['render']->addGlobal('id', $user->getId());
        $app['render']->addGlobal('username', $user->getUserName());
        $app['render']->addGlobal('email', $user->getEmail());

        return $app['render']->render('user_profile.twig');
    }

    /**
     * HTTP POST handler for user details change.
     */
    public function userEdit(Application $app, Request $request)
    {

    }

    /**
     * HTTP GET handler for the Sign Up page.
     */
    public function getSignup(Application $app)
    {
        $app['render']->addGlobal('title', 'Sign up');
        return $app['render']->render('signup.twig');
    }

    /**
     * HTTP POST handler for the Sign Up page.
     *
     * @TODO: password match check on the client side (javascript) too.
     * @TODO: AJAX check for username on the client side.
     * @TODO: Input sanitization.
     */
    public function postSignup(Application $app, Request $request)
    {
        $userAlreadyExists = true;

        if ($request->get('password') !== $request->get('password2')) {
            $app['session']->getFlashBag()->set('error', 'The two password does not match.');
            return $app->redirect('/signup');
        }

        $user = $app['user.factory'];
        try {
            $user->setUserName($request->get('username'))->retrieve();
        } catch (\UnexpectedValueException $e) {
            $userAlreadyExists = false;
        }

        if ($userAlreadyExists) {
            $app['session']->getFlashBag()->set('error', 'The username already exists.');
            return $app->redirect('/signup');
        }

        $email = $request->get('email');

        try {
            $user->setEmail($email);
        } catch (\InvalidArgumentException $e) {
            $app['session']->getFlashBag()
                           ->set('error', 'The entered e-mail address seems invalid. Please pick an other one.');
            return $app->redirect('/signup');
        }

        $user = $this->userSave($request, $app['user.factory']);

        $id = $user->getId();
        if (!empty($id)) {
            $app['session']->getFlashBag()
                ->set('success', 'Thank you for your sign up. Now please create your pets profile.');
            $subRequest = Request::create('/user/' . $id, 'GET');
            return $app->handle($subRequest, \Symfony\Component\HttpKernel\HttpKernelInterface::SUB_REQUEST);
        }

        $app['session']->getFlashBag()->set('error', 'The user could not be saved. Please try again later.');
        return $app->redirect('/signup');
    }

    /**
     * Wrapper for $app['user']->save()
     *
     * @param Request $request
     *   The HTTP POST request of the signup.
     * @param User $user
     *   The user entity to save.
     *
     * @return User
     *   The saved user with id.
     */
    protected function userSave(Request $request, User $user)
    {
        $user->setEmail($request->get('email'));
        $user->setUsername($request->get('username'));
        $user->setPassword($request->get('password'));
        $user->setEnabled(User::STATUS_ENABLED);
        $user->save();

        return $user;
    }
}
