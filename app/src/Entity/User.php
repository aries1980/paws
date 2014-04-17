<?php
/**
 * Contains Paws\Entity\User
 */

namespace Paws\Entity;

use Paws\Application;
use Paws\CrudInterface;
use Hautelook\Phpass\PasswordHash;

/**
 * Defines a user entity.
 *
 * @package Paws
 */
class User implements CrudInterface
{
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;
    const STATUS_DELETED = -1;
    const MINIMUM_HASH_STRENGTH = 8;

    /**
     * The DI container.
     *
     * @var \Paws\Application
     */
    protected $app;

    /**
     * A unique identifier of the user.
     *
     * @var string
     */
    protected $id;

    /**
     * A unique name of the user.
     *
     * @var string
     */
    protected $username;

    /**
     * The email address of the user.
     *
     * @var string
     */
    protected $email;

    /**
     * A password used to authenticate the.
     *
     * @var string
     */
    protected $password;

    /**
     * Whether the user allowed to log in.
     *
     * @var integer
     */
    protected $enabled;

    /**
     * Array of Role entities granted to the user.
     *
     * @var array
     */
    protected $roles;

    /**
     * Strenght of the password hash.
     *
     * @var integer
     */
    protected $hashStrength;

    /**
     * Hashed password, originated from self::password.
     *
     * @var string
     */
    protected $hashedPassword;

    public function __construct(Application $app, $username = '', array $roles = array(), $password = '', $email = '', $enabled = self::STATUS_ENABLED)
    {
        $this->app = $app;

        $this->setHashStrength($this->app['config']['hash_strength']);
        $this->setUserName($username);

        if (!empty($email)) {
            $this->setEmail($email);
        }

        $this->setPassword($password);
        $this->setEnabled($enabled);
        $this->setRoles($roles);
    }

    /**
     * Mutator for self::app.
     *
     * @param \Paws\Application $app
     *   The Application (DI) container.
     *
     * @return self
     *   Fluent interface.
     */
    public function setApp(Application $app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Accessor for self::app.
     *
     * @return \Paws\Application
     *   The application (DI) container.
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Mutator for self:hashStrength.
     *
     * @param integer $strength
     *   The password hash strength.
     *
     * @return self
     *   Fluent interface.
     */
    public function setHashStrength($strength)
    {
        $this->hashStrength = max($strength, self::MINIMUM_HASH_STRENGTH);

        return $this;
    }

    /**
     * Accessor for self:hashStrength.
     *
     * @return integer
     *   A strong enough hash complexity.
     */
    public function getHashStrength()
    {
        return $this->hashStrength;
    }

    /**
     * Mutator for self:id.
     *
     * @param integer $id
     *   The unique user id.
     *
     * @return self
     *   Fluent interface.
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Accessor for self:id.
     *
     * @return integer
     *   The identifier of the user.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Accessor for self::roles.
     *
     * @return Role[]
     *   The user roles.
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Accessor for self::password.
     *
     * @return string
     *   The encoded password for the user.
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Accessor for self::username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Accessor for self::email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Accessor for self::enabled
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Mutator for self:enabled.
     *
     * @param integer $status
     *   One of the defined status constant.
     *
     * @return self
     *   Fluent interface.
     */
    public function setEnabled($status)
    {
        $this->enabled = $status;

        return $this;
    }

    /**
     * Accessor to self::hashedPassword.
     *
     * @return string
     */
    public function getHashedPassword()
    {
        if (empty($this->hashedPassword)) {
            $passwordHash = $this->hash($this->getPassword());
            $this->setHashedPassword($passwordHash);
        }

        return $this->hashedPassword;
    }

    /**
     * Mutator to self:hashedPassword.
     *
     * @param string $password
     *    The hashed password.
     *
     * @return self
     *   Fluent interface.
     */
    public function setHashedPassword($password)
    {
        if ($password !== '') {
            $this->hashedPassword = $password;
        }
        else {
            $this->hashedPassword = null;
        }

        return $this;
    }

    /**
     * Secure hashing.
     *
     * @param string $string
     * @return string
     */
    public function hash($string) {
        // @TODO remove this wired-in hasher.
        $hasher = new PasswordHash($this->hashStrength, true);
        return $hasher->HashPassword($string);
    }

    /**
     * {inheritdoc}
     *
     * @TODO: throw an exception if the object is not properly initialized.
     */
    public function create()
    {
        $stmt = $this->app['db']->prepare("
            INSERT INTO user (username, password, email, enabled, created, modified)
            VALUES (:username, :password, :email, :enabled, NOW(), NOW())
        ");
        try {
        $stmt->execute([
            ':username' => $this->getUserName(),
            ':password' => $this->getHashedPassword(),
            ':email' => $this->getEmail(),
            ':enabled' => $this->getEnabled()
        ]);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }

        $id = $this->app['db']->lastInsertId();
        $this->setId($id);
    }

    /**
     * {inheritdoc}
     */
    public function retrieve()
    {
        if ($this->getId()) {
            $stmt = $this->app['db']->prepare("SELECT * FROM user WHERE id = :id");
            $stmt->execute([ ':id' => $this->getId() ]);
        } else {
            $stmt = $this->app['db']->prepare("SELECT * FROM user WHERE username = :username");
            $stmt->execute([ ':username' => $this->getUserName() ]);
        }

        if (!$user = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            throw new \UnexpectedValueException('There is no such user.');
        }

        $this->setId($user['id'])
             ->setEmail($user['email'])
             ->setUserName($user['username'])
             ->setHashedPassword($user['password']);

        return $this;
    }

    /**
     * {inheritdoc}
     *
     * @TODO: throw an exception if the object is not properly initialized.
     */
    public function update()
    {
        $stmt = $this->app['db']->prepare("
            UPDATE user
            SET username = :username, email = :email, enabled = :enabled, modified = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':username' => $this->getUsername,
            ':email' => $this->getEmail(),
            ':enabled' => $this->getEnabled(),
            ':id' => $this->getId()
        ]);

        // @TODO: merge the password update to the main update statement above.
        if (!empty($this->password)) {
            $stmt = $this->app['db']->prepare("UPDATE user SET password = :password WHERE id = :id");
            $stmt->execute([ ':password' => $this->getHashedPassword(), ':id' => $this->getId() ]);
        }
    }

    /**
     * Emulated deletion.
     *
     * Users are not allowed to be deleted, because data references would be stalled at the moment.
     * Instead the enabled value is changed.
     */
    public function delete()
    {
    }

    public function save()
    {
        if ($this->id) {
            $this->update();
        } else {
            $this->create();
        }

        return $this;
    }

    /**
     * Mutator for self::roles.
     *
     * @param array $roles
     * @throws \InvalidArgumentException
     */
    public function setRoles($roles = [])
    {
        foreach ($roles as $key => $role) {
            if (!$this->isValidRoleArgument($role)) {
                throw new \InvalidArgumentException('The role at key ' . $key . ' is not a valid argument');
            }
        }
    }

    /**
     * Checks if the role argument
     *
     * @param $roleId
     *   A role entity object.
     * @param $softcheck
     *   If true, it does just a lightweight formal check.
     *
     * @return bool
     *   True for a valid role argument, otherwise false.
     */
    public function isValidRoleArgument($roleId, $softcheck = true)
    {
        if (!$softcheck) {
            // @TODO: validate the object against real (stored) roles.
        }

        if (is_numeric($roleId) && $roleId > 0) {
            return true;
        }

        return false;
    }

    /**
     * Mutator for self::username.
     *
     * @param $username
     *   The unique username.
     *
     * @return self
     *   Fluent interface.
     */
    public function setUserName($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Mutator for self::email.
     *
     * @param $email
     *   The unique e-email address.
     *
     * @return self
     *   Fluent interface.
     *
     * @throws \InvalidArgumentException
     *   If the argument is not a valid e-email address.
     */
    public function setEmail($email)
    {
        if (!$this->isValidEmail($email)) {
            throw new \InvalidArgumentException($email . ' is not a valid e-mail.');
        }

        $this->email = $email;

        return $this;
    }

    /**
     * Encodes and sets the password.
     *
     * @param $password
     *   Unencrypted, raw password.
     *
     * @return self
     *   Fluent interface.
     */
    public function setPassword($password)
    {
        $this->password = $password;

        // Invalidate the stored hash.
        $this->setHashedPassword('');

        return $this;
    }

    /**
     * Checks the e-mail address format.
     *
     * @param $email
     *   A the e-mail address under test.
     *
     * @return bool
     *   True if the e-mail is valid, otherwise false.
     */
    public function isValidEmail($email)
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Checks the match of username and password.
     *
     * @return bool
     */
    public function checkCredentials() {
        // @TODO remove this wired-in hasher.
        $hasher = new PasswordHash($this->hashStrength, true);
        return $hasher->CheckPassword($this->getPassword(), $this->getHashedPassword());
    }

    /**
     * Authenticates the user.
     *
     * @TODO: refactor this to provide other auth providers such as OAuth2, LDAP, etc.
     */
    public function authenticate()
    {
        try {
            $this->retrieve();
        } catch (\UnexpectedValueException $e) {
            $this->app['session']->getFlashBag()->set('error', 'Incorrect username or password.');
            return false;
        } catch (\Exception $e) {
             $this->app['session']->getFlashBag()->set('error', 'Unknown error happened. Please try again later.');
             return false;
        }

        if (!$this->checkCredentials()) {
            // @TODO: provide hooks for throttling.
            $this->app['session']->getFlashBag()->set('error', 'Incorrect username or password.');
            return false;
        }

        if (!$this->getEnabled()) {
            $this->app['session']->getFlashBag()->set('error', 'Your account is blocked. Please contact the site administartor.');
        }

        // We wish to create a new session-id for extended security, but due to a bug in PHP < 5.4.11, this
        // will throw warnings. Suppress them here. #shakemyhead
        // @see: https://bugs.php.net/bug.php?id=63379
        @$this->app['session']->migrate(true);

        $user = clone $this;
        // Objects that contains closures can't be serialized.
        unset($user->app);
        $this->app['session']->set('user', $user);
    }

    /**
     * Log out the currently logged in user.
     *
     * @TODO: this should be in an authentication plugin.
     */
    public function logout()
    {
        $this->app['session']->getFlashBag()->set('info', 'You have been logged out.');
        $this->app['session']->remove('user');
        @$this->app['session']->migrate(true);

        // Remove the cookie..
        setcookie(
            $this->app['config']['session']['name'],
            '',
            time() -1,
            '/',
            $this->app['config']['session']['domain'],
            $this->app['config']['session']['https_only'],
            true
        );
    }

    /**
     * Checks if the user is not disabled meantime.
     */
    public function isValidSession()
    {
        $currentuser = $this->app['session']->get('user');
        if (!$currentuser) {
            return false;
        }

        // Load the user's record from the database.
        $this->retrieve();
        $id = $this->getId();
        if (empty($id)) {
            $this->logout();
            return false;
        }

        if ($currentuser->getEnabled() < 1) {
            // User has been disabled or deleted since logging in.
            $this->logout();
            return false;
        }
    }

    /**
     * Checks whether a user is allowed to do a given action.
     *
     * @TODO: move this to the Permission service provider.
     * @TODO: this is a stub.
     */
    public function isAllowed($action)
    {
        return true;
    }

}
