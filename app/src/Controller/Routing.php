<?php

namespace Paws\Controller;
use Silex;
use Silex\ControllerProviderInterface;


class Routing implements ControllerProviderInterface
{

    public function connect(Silex\Application $app)
    {
        $ctl = $app['controllers_factory'];

        $ctl->get('', [$this, 'dashboard'])
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

    protected function home(\Paws\Application $app) {
        $template = 'index.twig';
        return $app['render']->render($template);
    }
}
