<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\LongRunningNode\Model;

use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;

/**
 * Class LongRunningNodeAbstract
 *
 * @package Hanaboso\PipesPhpSdk\LongRunningNode\Model
 */
abstract class LongRunningNodeAbstract implements LongRunningNodeInterface
{

    /**
     * @var ApplicationInterface|null
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
     * @return ApplicationInterface
     * @throws ConnectorException
     */
    public function getApplication(): ApplicationInterface
    {
        if ($this->application) {
            return $this->application;
        }

        throw new ConnectorException('Application has not set.', ConnectorException::MISSING_APPLICATION);
    }

    /**
     * @return string|null
     */
    public function getApplicationKey(): ?string
    {
        if ($this->application) {
            return $this->application->getKey();
        }

        return NULL;
    }

}
