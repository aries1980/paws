<?php

namespace Paws\Controller;

use Symfony\Component\HttpFoundation\Request;
use Silex\ControllerProviderInterface;
use Paws\Application;

class Routing implements ControllerProviderInterface
{

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
        switch ($request->get('action')) {
            case 'login':
                $user = $app['user.factory'];
                $result = $app['user']->login($request->get('username'), $request->get('password'));

                if ($result) {
                    $app['log']->add("Login " . $request->get('username'), 3, '', 'login');
                    $retreat = $app['session']->get('retreat');
                    $redirect = !empty($retreat) && is_array($retreat) ? $retreat : array('route' => 'dashboard', 'params' => array());
                    return redirect($redirect['route'], $redirect['params']);
                }
                return $this->getLogin($app, $request);

            default:
                // Let's not disclose any internal information.
                return $app->abort(400, 'Invalid request');
        }
    }


    public function before()
    {

    }
}
