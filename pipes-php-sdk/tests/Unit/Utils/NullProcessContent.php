<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Utils;

use Hanaboso\PipesPhpSdk\Utils\ProcessContentTrait;

/**
 * Class NullProcessContent
 *
 * @package PipesPhpSdkTests\Unit\Utils
 */
final class NullProcessContent
{

    use ProcessContentTrait;

    /**
     * @return string
     */
    public function getId(): string
    {
        return '1';
    }

}
