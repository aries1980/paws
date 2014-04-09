<?php
/**
 * Contains Paws\User
 */

namespace Paws;

use Silex;

/**
 * Defines a user entity.
 *
 * @package Paws
 */
class User {
    private $username;
    private $password;
    private $enabled;
    private $accountNonExpired;
    private $credentialsNonExpired;
    private $accountNonLocked;
    private $roles;
} 
