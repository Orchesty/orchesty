<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class EmbedSubscriber
 *
 * @package CleverConnectors\AppBundle\Document
 *
 * @ODM\EmbeddedDocument()
 */
class EmbedSubscriber
{

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $email;

    /**
     * EmbedSubscriber constructor.
     *
     * @param string $email
     */
    public function __construct(string $email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return EmbedSubscriber
     */
    public function setEmail(string $email): EmbedSubscriber
    {
        $this->email = $email;

        return $this;
    }

}