<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Document\Dto;

use Hanaboso\Utils\String\Json;

/**
 * Class CommonObjectDto
 *
 * @package Hanaboso\PipesPhpSdk\Application\Document\Dto
 */
final class CommonObjectDto
{

    public const string NAME = 'name';
    public const string APP  = 'app';

    /**
     * CommonObjectDto constructor.
     *
     * @param string      $name
     * @param string|null $app
     */
    public function __construct(private string $name = '', private ?string $app = '')
    {
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return Json::encode(
            $this->toArray(),
        );
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getApp(): ?string
    {
        return $this->app;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array {
        return [
            self::APP  => $this->getApp(),
            self::NAME => $this->getName(),
        ];
    }

}
