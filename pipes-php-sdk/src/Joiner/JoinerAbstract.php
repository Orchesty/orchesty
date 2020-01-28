<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Joiner;

use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;

/**
 * Class JoinerAbstract
 *
 * @package Hanaboso\PipesPhpSdk\Joiner
 */
abstract class JoinerAbstract implements JoinerInterface
{

    /**
     * @var ApplicationInterface|null
     */
    protected $application;

    /**
     * @param mixed[] $data
     * @param int     $count
     *
     * @return string[]
     */
    public function process(array $data, int $count): array
    {
        $this->save($data);

        $res = ['Incomplete data'];
        if ($this->isDataComplete($count)) {
            $res = $this->runCallback();
        }

        return $res;
    }

    /**
     * @param ApplicationInterface $application
     *
     * @return JoinerInterface
     */
    public function setApplication(ApplicationInterface $application): JoinerInterface
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
