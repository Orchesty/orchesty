<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Tomas Sedlacek
 * Mail: mail@kedlas.cz
 * Date: 29/09/2017
 * Time: 13:32
 */

namespace CleverConnectors\AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class Settings
 *
 * @package CleverConnectors\AppBundle\Document
 *
 * @ODM\EmbeddedDocument
 * @ODM\HasLifecycleCallbacks
 */
class Settings
{

    /**
     * @var string
     * @ODM\Field(type="string")
     */
    private $username;

    /**
     * @var string
     * @ODM\Field(type="string")
     */
    private $password;

    /**
     * @var string
     * @ODM\Field(type="string")
     */
    private $accessToken;

    /**
     * @var string
     * @ODM\Field(type="string")
     */
    private $redirectUrl;

    /**
     * Settings constructor.
     *
     * @param string $username
     * @param string $password
     * @param string $accessToken
     * @param string $redirectUrl
     */
    public function __construct(
        string $username = '',
        string $password = '',
        string $accessToken = '',
        string $redirectUrl = ''
    )
    {
        $this->username = $username;
        $this->password = $password;
        $this->accessToken = $accessToken;
        $this->redirectUrl = $redirectUrl;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * @return string
     */
    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    /**
     * TODO
     *
     * @ODM\PreFlush
     */
    public function encrypt() {

    }

    /**
     * TODO
     *
     * @ODM\PostLoad
     */
    public function decrypt() {

    }

}
