<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\CustomNode;

use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;

/**
 * Class CustomNodeAbstract
 *
 * @package Hanaboso\PipesPhpSdk\CustomNode
 */
abstract class CustomNodeAbstract implements CustomNodeInterface
{

    /**
     * @var ApplicationInterface|null
     */
    protected $application;

    /**
     * @param ApplicationInterface $application
     *
     * @return CustomNodeInterface
     */
    public function setApplication(ApplicationInterface $application): CustomNodeInterface
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
