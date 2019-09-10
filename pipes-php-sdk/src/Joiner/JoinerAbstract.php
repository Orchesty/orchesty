<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Joiner;

use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;

/**
 * Class JoinerAbstract
 *
 * @package Hanaboso\PipesPhpSdk\Joiner
 */
abstract class JoinerAbstract implements JoinerInterface
{

    /**
     * @var ApplicationInterface
     */
    protected $application;

    /**
     * @param array $data
     * @param int   $count
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
