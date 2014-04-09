<?php
/**
 * @file
 * Contains Paws\Role
 */

namespace Paws\Entity;

use Paws\Application;
use Paws\CrudInterface;

/**
 * Defines a role entity for user accounts.
 *
 * @package Paws
 */
class Role implements CrudInterface
{
    /**
     * self::id of the non-logged in user.
     */
    const ANONYMOUS = 0;

    /**
     * self:id of the logged in user
     */
    const AUTHENTICATED = 1;

    /**
     * The unique identifier of the role.
     *
     * @var integer
     */
    private $id;

    /**
     * The name of the role that will be displayed to the end-user.
     *
     * @var string
     */
    private $name;

    public function __construct(Application $app, $id = self::ANONYMOUS)
    {
        $this->app = $app;
        $this->id = $id;
    }

    /**
     * {inheritdoc}
     */
    public function create()
    {
        // TODO: Implement create() method.
    }

    /**
     * {inheritdoc}
     */
    public function retrieve()
    {
        $stmt = $this->app['db']->prepare("SELECT * FROM role WHERE id = :id");
        $stmt->execute([ ':id' => $this->id ]);

        $role = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->setId($role['id'])->setName($role['name']);
    }

    /**
     * {inheritdoc}
     */
    public function update()
    {
        // TODO: Implement update() method.
    }

    /**
     * {inheritdoc}
     */
    public function delete()
    {
        // TODO: Implement delete() method.
    }

    /**
     * Creates or Updates the object.
     */
    public function save()
    {
        // TODO: Implement save() method.
    }

    public function addUserToRole($id) {

    }
} 
