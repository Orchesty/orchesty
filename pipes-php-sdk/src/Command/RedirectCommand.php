<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Command;

use Hanaboso\CommonsBundle\Redirect\RedirectInterface;

/**
 * Class RedirectCommand
 *
 * @package Hanaboso\PipesPhpSdk\Command
 */
class RedirectCommand implements RedirectInterface
{

    /**
     * @param string $url
     */
    public function make(string $url): void
    {
        echo sprintf('%s%s', $url, PHP_EOL);
    }

}