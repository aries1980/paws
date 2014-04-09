<?php
/**
 * @file
 * Contains Paws\Application
 */

namespace Paws;

use Silex;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Paws\Provider\UserServiceProvider;
use DerAlex\Silex\YamlConfigServiceProvider;

/**
 * Paws Application service locator.
 *
 * @package Paws
 */
class Application extends Silex\Application {
    /**
     * {inheritdoc}
     */
    public function __construct(array $values = array())
    {
        parent::__construct($values);

        // Config needs to be initialized here for the debug option.
        $this->initConfig();
        $this['debug'] = $this['config']['debug']['enabled'];
    }

    /**
     * Initializes the config and session providers.
     */
    private function initConfig()
    {
        $this->register(new YamlConfigServiceProvider(PAWS_CONFIG_DIR . '/common.yml'))
             ->register(new YamlConfigServiceProvider(PAWS_CONFIG_DIR . '/routing.yml'));
//             ->register(new YamlConfigServiceProvider(PAWS_CONFIG_DIR . '/menu.yml'));
    }

    /**
     * Boots Paws.
     */
    public function initialize()
    {
        $this->initSession($this['config']['session'])
             ->initLogger($this['config']['logger'])
             ->initDatabase($this['config']['database'])
             ->initUserService()
             ->initRendering()
             ->initRouteHandlers();
    }

    /**
     * Initializes the session provider.
     *
     * @param $config
     *   Session options.
     *
     * @return self
     *   Fluent interface.
     */
    protected function initSession($config)
    {
        $this->register(
            new SessionServiceProvider(),
            ['session.storage.options' => [
                'name'            => $config['name'],
                'cookie_secure'   => $config['https_only'],
                'cookie_httponly' => true
            ]]
        );

        // Disable Silex's built-in native filebased session handler, and fall back to
        // whatever's set in php.ini.
        // @see: http://silex.sensiolabs.org/doc/providers/session.html#custom-session-configurations
        if ($config['use_storage_handler'] === false) {
            $this['session.storage.handler'] = null;
        }

        return $this;
    }

    /**
     * Initializes Monolog logger provider.
     *
     * No need for additional wrapper, because Monolog is PSR-3 compatible.
     *
     * @param $config
     *   Configuration options for Monolog.
     *
     * @return self
     *   Fluent interface.
     */
    public function initLogger($config)
    {
        $this->register(new MonologServiceProvider(), $config);

        return $this;
    }

    /**
     * Initializes User service provider.
     *
     * @return self
     *   Fluent interface.
     */
    public function initUserService()
    {
        $this->register(new UserServiceProvider());

        return $this;
    }

    /**
     * Initializes the database provider.
     *
     * @param array $config
     *   Options for PDO.
     *
     * @return self
     *   Fluent interface.
     */
    public function initDatabase($config)
    {
        $pdoDsn = $config['driver'] .
            ':host=' . $config['host'] .
            ';dbname=' . $config['name'] .
            ';charset=' . $config['charset'];

        try {
            $this['db'] = new \PDO($pdoDsn, $config['username'], $config['password']);
        } catch (\PDOException $e) {
            $this['logger']->emergency(
                'Database {name} on {host} is not accessible with the given credentials.',
                ['name' => $config['name'], 'host' => $config['host']]
            );
        }

        return $this;
    }

    /**
     * Initiates renderer provider.
     *
     * @return self
     *   Fluent interface.
     *
     * @TODO: Provide default template.
     * @TODO: Provide fallback to the default template if no custom template available.
     * @TODO: A generic RendererProvider to wrap Twig.
     */
    protected function initRendering()
    {
        if (!empty($this['config']['theme']['location'])) {
            $themeBasePath = PAWS_WEB_DIR . '/' . $this['config']['theme']['location'];
        } else {
            $themeBasePath = PAWS_WEB_DIR . '/theme';
        }

        $themePath = array(realpath($themeBasePath . '/' . $this['config']['theme']['name']));

        $this->register(
            new TwigServiceProvider(),
            [
                'twig.path'    => $themePath,
                'twig.options' => [
                    'debug'            => true,
                    'cache'            => PAWS_CACHE_DIR,
                    'strict_variables' => $this['config']['strict_variables'],
                    'autoescape'       => true,
                ]
            ]
        );

        $this['render'] = $this['twig'];
        $this->addGlobalVariablesToTwig();

        return $this;
    }

    protected function addGlobalVariablesToTwig() {

        $hostName = empty($_SERVER['HTTP_HOST']) ? 'localhost' : $_SERVER['HTTP_HOST'];

        $themePath = $this['config']['url_prefix'] . '/' .
            $this['config']['theme']['location'] . '/' .
            $this['config']['theme']['name'];

        // Set the paths
        $path = array(
            'hostname' => $hostName,
            'root' => PAWS_WEB_DIR,
            'theme' => $themePath,
        );

        $this['twig']->addGlobal('path', $path);
    }

    /**
     * Inits service providers.
     */
    public function initProviders()
    {
        $this->register(new Silex\Provider\UrlGeneratorServiceProvider())
             ->register(new Silex\Provider\FormServiceProvider())
             ->register(new Silex\Provider\ValidatorServiceProvider());
    }

    public function initRouteHandlers()
    {
        // Mount the 'frontend' controllers, ar defined in our Routing.yml
        $this->mount('', new Controller\Routing());

        return $this;
    }

    public function initAfterHandler()
    {
        // On 'after' attach the debug-bar, if debug is enabled..
        if ($this['debug'] && ($this['session']->has('user') || $this['config']['debug']['show_loggedoff'])) {

            // Set the error_reporting to the level specified in common.yml.
            error_reporting($this['config']['debug']['error_level']);
        } else {
            // Even if debug is not enabled,
            error_reporting(E_ALL &~ E_NOTICE &~ E_DEPRECATED &~ E_USER_DEPRECATED);
        }

        $this->after([$this, 'afterHandler']);
    }

    /**
     * Global 'after' handler. Adds 'after' HTML-snippets and META-headers to the output.
     *
     * @param Request $request
     * @param Response $response
     */
    public function afterHandler(Request $request, Response $response)
    {
        // @TODO: add content-based snippets/extensions here.
    }

    /**
     * Handles errors thrown in the application.
     *
     * @param \Exception $exception
     *   The thrown exception.
     *
     * @return Response
     */
    public function ErrorHandler(\Exception $exception)
    {
        // If we are in maintenance mode and current user is not logged in, show maintenance notice.
        // On a scalable system this shoud happen in the HTTP traffic manager such as Varnish, Stingray, etc.
        if (!empty($this['config']['maintenance_mode']['enabled'])) {
            $user = $this['users']->getCurrentUser();
            if ($user['userlevel'] < 2) {
                $template = $this['config']['maintenance_mode']['template'];
                $body = $this['render']->render($template);
                return new Response($body, 503);
            }
        }

        $paths = getPaths($this['config']);

        $twigvars = array();

        $twigvars['class'] = get_class($exception);
        $twigvars['message'] = $exception->getMessage();
        $twigvars['code'] = $exception->getCode();
        $twigvars['paths'] = $paths;
        $twigvars['trace'] = $exception->getTrace();
        $twigvars['title'] = 'An error has occurred!';

        $this['log']->add($twigvars['message'], 2, '', 'abort');

        return $this['render']->render('error.twig', $twigvars);
    }
}
