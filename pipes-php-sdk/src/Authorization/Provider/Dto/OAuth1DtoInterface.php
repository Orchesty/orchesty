<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Provider\Dto;

/**
 * Interface OAuth1DtoInterface
 *
 * @package Hanaboso\PipesPhpSdk\Authorization\Provider\Dto
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
