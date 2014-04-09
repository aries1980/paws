<?php
/**
 * @file
 * Contains Paws\Test\UserTest
 */

namespace Paws\Test;

use Paws\Application;
use Paws\Entity\User;
use Paws\Entity\Role;

/**
 * Tests against Paws\Entity\User.
 *
 * @package Paws
 */
class UserTest extends \PHPUnit_Framework_TestCase
{
    const USERNAME = 'John Doe';
    const EMAIL = 'john.doe@example.com';
    const PASSWORD = 'Password of John Doe';
    const ROLE = Role::AUTHENTICATED;

    /**
     * @var Paws\Application
     */
    protected $app;

    /**
     * @var Paws\Entity\User
     */
    protected $user;

    public function setUp()
    {
        $this->app = new Application();
        $this->user = new User($this->app, self::EMAIL, self::USERNAME, self::PASSWORD, array(self::ROLE));
    }

    public function testGetHashedPassword()
    {
        $this->user->setHashedPassword('');

        $passwordHash = $this->user->getHashedPassword();
        $this->assertNotEmpty($passwordHash);
    }

    public function testIsValidRoleArgument()
    {
        $this->assertFalse($this->user->isValidRoleArgument('meh'));
        $this->assertTrue($this->user->isValidRoleArgument(1));
    }

    public function testIsValidEmail()
    {
        $this->assertFalse($this->user->isValidEmail('bad email@example.com'));
        $this->assertTrue($this->user->isValidEmail('good+email@example.com'));
        $this->assertTrue($this->user->isValidEmail('good.email@example.com'));
        // Bug in PHP 5.5. Unit test fa
        $this->assertFalse($this->user->isValidEmail('good.email@localhost', 'Bug existed PHP 5.5. Now it is fixed.'));
        // Bug in PHP 5.5.
        $this->assertFalse($this->user->isValidEmail('good.@example.com', 'Bug existed PHP 5.5. Now it is fixed.'));
    }

    public function testSetHashStrength()
    {
        // At constructor initialization the self::hashStrength is set to the minimum.
        $this->assertEquals($this->user->getHashStrength(), User::MINIMUM_HASH_STRENGTH);

        $notStrongEnoughHash = User::MINIMUM_HASH_STRENGTH - 1;
        $this->user->setHashStrength($notStrongEnoughHash);

        $this->assertEquals($this->user->getHashStrength(), User::MINIMUM_HASH_STRENGTH);

        $strongEnoughHash = User::MINIMUM_HASH_STRENGTH + 1;
        $this->user->setHashStrength($strongEnoughHash);
        $this->assertEquals($this->user->getHashStrength(), $strongEnoughHash);
    }
}
