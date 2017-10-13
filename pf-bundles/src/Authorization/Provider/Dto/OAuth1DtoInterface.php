<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 25.9.17
 * Time: 17:20
 */

namespace Hanaboso\PipesFramework\Authorization\Provider\Dto;

/**
 * Interface OAuth1DtoInterface
 *
 * @package Hanaboso\PipesFramework\Authorization\Provider\Dto
 */
interface OAuth1DtoInterface
{

    /**
     * @return string
     */
    public function getConsumerKey(): string;

    /**
     * @return string
     */
    public function getConsumerSecret(): string;

    /**
     * @return string
     */
    public function getSignatureMethod(): string;

    /**
     * @return int
     */
    public function getAuthType(): int;

    /**
     * @return array
     */
    public function getToken(): array;

}