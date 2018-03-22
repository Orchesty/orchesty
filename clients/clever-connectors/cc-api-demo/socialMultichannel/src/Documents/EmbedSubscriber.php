<?php declare(strict_types=1);

namespace CleverCore\SocialMultichannel\Documents;

/**
 * Class EmbedSubscriber
 *
 * @package CleverCore\SocialMultichannel\Documents
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