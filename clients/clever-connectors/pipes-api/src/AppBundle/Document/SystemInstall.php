<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Document;

use CleverConnectors\AppBundle\Document\Traits\IdTrait;
use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class SystemInstall
 *
 * @package CleverConnectors\AppBundle\Document
 *
 * @ODM\Document(repositoryClass="CleverConnectors\AppBundle\Repository\SystemInstallRepository")
 */
class SystemInstall
{

    use IdTrait;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     * @ODM\Index()
     */
    protected $user;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected $token;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected $system;

    /**
     * @var bool
     *
     * @ODM\Field(type="boolean")
     */
    protected $synchronized;

    /**
     * @var DateTime
     *
     * @ODM\Field(type="date")
     */
    protected $created;

    /**
     * SystemInstall constructor.
     */
    public function __construct()
    {
        $this->created = new DateTime();
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @param string $user
     *
     * @return SystemInstall
     */
    public function setUser(string $user): SystemInstall
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     *
     * @return SystemInstall
     */
    public function setToken(string $token): SystemInstall
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return string
     */
    public function getSystem(): string
    {
        return $this->system;
    }

    /**
     * @param string $system
     *
     * @return SystemInstall
     */
    public function setSystem(string $system): SystemInstall
    {
        $this->system = $system;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSynchronized(): bool
    {
        return $this->synchronized;
    }

    /**
     * @param bool $synchronized
     *
     * @return SystemInstall
     */
    public function setSynchronized(bool $synchronized): SystemInstall
    {
        $this->synchronized = $synchronized;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @param DateTime $created
     *
     * @return SystemInstall
     */
    public function setCreated(DateTime $created): SystemInstall
    {
        $this->created = $created;

        return $this;
    }

}