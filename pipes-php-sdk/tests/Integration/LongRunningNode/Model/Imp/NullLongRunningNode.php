<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\LongRunningNode\Model\Imp;

use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\LongRunningNode\Model\Impl\LongRunningNodeAbstract;
use Hanaboso\PipesPhpSdk\LongRunningNode\Model\LongRunningNodeInterface;

/**
 * Class NullLongRunningNode
 *
 * @package PipesPhpSdkTests\Integration\LongRunningNode\Model\Imp
 */
final class NullLongRunningNode extends LongRunningNodeAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'null';
    }

    /**
     * @param ApplicationInterface $application
     *
     * @return LongRunningNodeInterface
     */
    public function setApplication(ApplicationInterface $application): LongRunningNodeInterface
    {
        $application;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getApplicationKey(): ?string
    {
        return 'null';
    }

}
