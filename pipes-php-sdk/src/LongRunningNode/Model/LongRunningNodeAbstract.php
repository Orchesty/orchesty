<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\LongRunningNode\Model;

use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;

/**
 * Class LongRunningNodeAbstract
 *
 * @package Hanaboso\PipesPhpSdk\LongRunningNode\Model
 */
abstract class LongRunningNodeAbstract implements LongRunningNodeInterface
{

    /**
     * @var ApplicationInterface
     */
    protected $application;

    /**
     * @param ApplicationInterface $application
     *
     * @return LongRunningNodeInterface
     */
    public function setApplication(ApplicationInterface $application): LongRunningNodeInterface
    {
        $this->application = $application;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getApplicationKey(): ?string
    {
        /** @var ApplicationInterface|null $application */
        $application = $this->application;
        if ($application) {

            return $application->getKey();
        }

        return NULL;
    }

}