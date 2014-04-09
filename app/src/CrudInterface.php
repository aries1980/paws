<?php
/**
 * @file
 * Contains Paws\CrudInterface
 */

namespace Paws;

/**
 * Provides a generic CRUD interface to store objects.
 *
 * @package Paws
 */
interface CrudInterface
{
    public function create();

    /**
     * Populates the values of the current entity from the database.
     *
     * @return self
     *   Fluent interface.
     *
     * @throws \UnexpectedValueException
     *   If there is no such user in the database.
     */
    public function retrieve();
    public function update();
    public function delete();

    /**
     * Creates or Updates the object.
     */
    public function save();
}
