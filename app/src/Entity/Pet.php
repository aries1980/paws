<?php
/**
 * Created by PhpStorm.
 * User: aries
 * Date: 2014.04.09.
 * Time: 0:30
 */

namespace Paws\Entity;

class Pet
{
    private $id;
    private $ownerId;
    private $name;
    private $gender;
    private $thumbsUp;
    private $species;
    private $breed;
    private $photos;
    private $description;

    public function getId()
    {
        return $this->id;
    }

    public function ownerId()
    {
        return $this->ownerId;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getGender()
    {
        return $this->gender;
    }

    public function getSpecies()
    {
        return $this->species;
    }

    public function getThumbsUp()
    {
        return $this->thumbsUp;
    }

    public function getBreed()
    {
        return $this->breed;
    }

    public function getPhotos()
    {
        return $this->photos;
    }

    public function getDescription()
    {
        return $this->description;
    }
}
