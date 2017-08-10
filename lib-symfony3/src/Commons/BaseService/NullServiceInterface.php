<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\BaseService;

/**
 * Class NullServiceInterface
 *
 * @package Hanaboso\PipesFramework\Commons\BaseService
 */
class NullServiceInterface implements BaseServiceInterface
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function getServiceType(): string
    {
        return '';
    }

}