<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Base;

use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;

/**
 * Interface OAuth1ApplicationInterface
 *
 * @package Hanaboso\PipesFramework\Application\Base
 */
interface OAuth1ApplicationInterface extends BasicApplicationInterface
{

    /**
     * @param ApplicationInstall $applicationInstall
     */
    public function authorize(ApplicationInstall $applicationInstall): void;

}