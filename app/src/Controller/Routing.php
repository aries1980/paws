<?php

namespace Paws\Controller;

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


    public function before()
    {

    }
}
