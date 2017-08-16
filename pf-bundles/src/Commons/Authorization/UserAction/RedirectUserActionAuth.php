<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 17.3.2017
 * Time: 17:33
 */

namespace Hanaboso\PipesFramework\Commons\Authorization\UserAction;

use JMS\Serializer\Annotation as Serializer;

/**
 * Class RedirectUserActionAuth
 *
 * @package Hanaboso\PipesFramework\Commons\Authorization\UserAction
 */
class RedirectUserActionAuth extends UserActionAuthObject
{

    /**
     * @Serializer\Type("string")
     *
     * @var string
     */
    private $url;

    /**
     * RedirectUserActionAuth constructor.
     *
     * @param string $url
     */
    public function __construct(string $url)
    {
        parent::__construct('url_redirect');
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return RedirectUserActionAuth
     */
    public function setUrl(string $url): RedirectUserActionAuth
    {
        $this->url = $url;

        return $this;
    }

}