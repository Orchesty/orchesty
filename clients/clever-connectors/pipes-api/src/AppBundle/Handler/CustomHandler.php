<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Handler;

use CleverConnectors\AppBundle\Model\CustomNode\CustomManager;

/**
 * Class CustomHandler
 *
 * @package CleverConnectors\AppBundle\Handler
 */
final class CustomHandler
{

    /**
     * @var CustomManager
     */
    private $manager;

    /**
     * @param CustomManager $manager
     */
    public function __construct(CustomManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param string $systemKey
     * @param string $action
     * @param array  $data
     *
     * @return array
     * @throws \CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException
     */
    public function runCustomSystemAction(string $systemKey, string $action, array $data = []): array
    {
        return $this->manager->runCustomSystemAction($systemKey, $action, $data);
    }

}
