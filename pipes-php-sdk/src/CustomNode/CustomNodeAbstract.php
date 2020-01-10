<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\CustomNode;

use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;

/**
 * Class CustomNodeAbstract
 *
 * @package Hanaboso\PipesPhpSdk\CustomNode
 */
abstract class CustomNodeAbstract implements CustomNodeInterface
{

    /**
     * @var ApplicationInterface
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