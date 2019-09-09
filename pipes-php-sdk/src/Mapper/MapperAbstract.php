<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Mapper;

use Hanaboso\PipesPhpSdk\Authorization\Base\ApplicationInterface;

/**
 * Class MapperAbstract
 *
 * @package Hanaboso\PipesPhpSdk\Mapper
 */
abstract class MapperAbstract implements MapperInterface
{

    /**
     * @var ApplicationInterface
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