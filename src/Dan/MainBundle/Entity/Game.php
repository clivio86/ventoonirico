<?php

namespace Dan\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Yaml\Yaml;
use JMS\Serializer\Annotation as Serializer;

/**
 * Game
 *
 * @ORM\Table(name="dan_game")
 * @ORM\Entity(repositoryClass="Dan\MainBundle\Entity\GameRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Game
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose
     * @Serializer\Type("integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    private $name;

    /**
     * @var array
     *
     * @ORM\Column(name="owners", type="array")
     * @Serializer\Expose
     * @Serializer\Type("array<string>")
     */
    private $owners;

    /**
     * @var string
     *
     * @ORM\Column(name="thumbnail", type="text")
     * @Serializer\Expose
     * @Serializer\Type("string")
     */
    private $thumbnail;

    /**
     * @var integer
     *
     * @ORM\Column(name="minPlayer", type="integer")
     * @Serializer\Expose
     * @Serializer\Type("integer")
     */
    private $minPlayer;

    /**
     * @var integer
     *
     * @ORM\Column(name="maxPlayers", type="integer")
     * @Serializer\Expose
     * @Serializer\Type("integer")
     */
    private $maxPlayers;


    public function __construct($item=null, $options = null)
    {
        if (isset($item)) {
            $this->loadFromItem($item, $options);
        }
    }
    
    public function loadFromItem($item, $options=null)
    {
                if (isset($options)) {
            if (isset($options['user'])) {
                $attributes = $item->status[0]->attributes();
                if ( (bool)(int)$attributes['own']) {
                    $this->addOwner($options['user']);
                }
            }
            if (isset($options['owners'])) {
                $this->setOwners($options['owners']);
            }

            if (isset($options['owner'])) {
                $this->addOwner($options['owner']);
            }
        }
        
        $comment = trim((string)$item->comment);
        if ($comment) {
            try {
                $data = Yaml::parse($comment);
                if (is_array($data)) {
                    $owner = null;
                    if (isset($data['Owner'])) {
                        $owner = $data['Owner'];                    
                    }
                    if (isset($data['owner'])) {
                        $owner = $data['owner'];                    
                    }
                    if ($owner) {
                        $this->setOwners(array($owner));
                    }
                }
            } catch (\Exception $e) {}
        }
        $attributes = $item->attributes();
        $this->setId((int) $attributes['objectid']);

        $this->setName((string) $item->name);
        $this->setThumbnail((string) $item->thumbnail);
        $attributes = $item->stats[0]->attributes();
        $this->setMinPlayers((int) $attributes['minplayers']);
        $this->setMaxPlayers((int) $attributes['maxplayers']);
    }
    
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Game
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set owners
     *
     * @param array $owners
     * @return Game
     */
    public function setOwners($owners)
    {
        $this->owners = $owners;
    
        return $this;
    }

    /**
     * Get owners
     *
     * @return array 
     */
    public function getOwners()
    {
        return $this->owners;
    }
    
    public function getOwner()
    {
        return $this->owners[0];
    }
    
    public function addOwner($owner)
    {
        $this->owners[] = $owner;
    }

    /**
     * Set thumbnail
     *
     * @param string $thumbnail
     * @return Game
     */
    public function setThumbnail($thumbnail)
    {
        $this->thumbnail = $thumbnail;
    
        return $this;
    }

    /**
     * Get thumbnail
     *
     * @return string 
     */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * Set minPlayer
     *
     * @param integer $minPlayer
     * @return Game
     */
    public function setMinPlayer($minPlayer)
    {
        $this->minPlayer = $minPlayer;
    
        return $this;
    }

    /**
     * Get minPlayer
     *
     * @return integer 
     */
    public function getMinPlayer()
    {
        return $this->minPlayer;
    }

    /**
     * Set maxPlayers
     *
     * @param integer $maxPlayers
     * @return Game
     */
    public function setMaxPlayers($maxPlayers)
    {
        $this->maxPlayers = $maxPlayers;
    
        return $this;
    }

    /**
     * Get maxPlayers
     *
     * @return integer 
     */
    public function getMaxPlayers()
    {
        return $this->maxPlayers;
    }
}
