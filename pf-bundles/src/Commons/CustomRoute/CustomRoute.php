<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 20.3.2017
 * Time: 11:09
 */

namespace Hanaboso\PipesFramework\Commons\CustomRoute;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CustomRoute
 *
 * @package Hanaboso\PipesFramework\Commons\CustomRoute
 */
class CustomRoute implements RouteInterface
{

    /**
     * @Serializer\Type("string")
     * @var string
     */
    private $method;

    /**
     * @Serializer\Type("string")
     * @var string
     */
    private $partUrl;

    /**
     * @Serializer\Type("string")
     * @var string|null
     */
    private $caption;

    /**
     * CustomRoute constructor.
     *
     * @param string      $method
     * @param string      $partUrl
     * @param null|string $caption
     */
    public function __construct(string $method, string $partUrl, ?string $caption = NULL)
    {
        $this->method  = $method;
        $this->partUrl = $partUrl;
        $this->caption = $caption;
    }

    /**
     * @param Request $request
     * @param string  $partUrl
     *
     * @return bool
     */
    public function isSuitable(Request $request, string $partUrl): bool
    {
        return $this->partUrl == $partUrl && $this->method == $request->getMethod();
    }

    /**
     * @param string $baseUrl
     *
     * @return string
     */
    public function getUrl(string $baseUrl): string
    {
        return $baseUrl . $this->partUrl;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return string|null
     */
    public function getCaption(): ?string
    {
        return $this->caption;
    }

}