<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Class ServiceSystemManager
 *
 * @package CleverConnectors\AppBundle\Model\Systems
 */
final class ServiceSystemManager
{

    /**
     * @var DocumentManager
     */
    private $dm;
    /**
     * @var SystemLoader
     */
    private $systemLoader;

    /**
     * @param DocumentManager $dm
     * @param SystemLoader    $systemLoader
     */
    public function __construct(DocumentManager $dm, SystemLoader $systemLoader)
    {
        $this->dm = $dm;
        $this->systemLoader = $systemLoader;
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
        /** @var SystemInterface $system */
        $system = $this->systemLoader->getSystem($systemKey);

        if (method_exists($system, $action)) {
            $output = $system->$action($this->createSystemInstall(), $data);
            $this->dm->flush();

            return $output;
        }

        throw new SystemException(
            sprintf('Action "%s" does not exist for "%s" system.', $action, $systemKey),
            SystemException::SYSTEM_METHOD_NOT_FOUND
        );
    }

    /**
     * @return SystemInstall
     */
    private function createSystemInstall(): SystemInstall
    {
        $si = new SystemInstall();
        $si->setUser('unknown');

        return $si;
    }

}
