<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Mapper;

use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;

/**
 * Class MapperAbstract
 *
 * @package Hanaboso\PipesPhpSdk\Mapper
 */
abstract class MapperAbstract implements MapperInterface
{

    /**
     * @var ApplicationInterface|null
     */
    protected $application;

    /**
     * @param ApplicationInterface $application
     *
     * @return MapperInterface
     */
    public function setApplication(ApplicationInterface $application): MapperInterface
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
