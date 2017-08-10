<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 16.3.2017
 * Time: 17:53
 */

namespace Hanaboso\PipesFramework\Commons\BaseService;

/**
 * Interface BaseServiceInterface
 *
 * @package Hanaboso\PipesFramework\Commons\BaseService
 */
interface BaseServiceInterface
{

    public const AUTHORIZATION = 'authorization';
    public const CONNECTOR     = 'connector';

    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return string
     */
    public function getServiceType(): string;

}