<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\OpenSourcePlugins;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;

/**
 * Class OpenSourcePluginsSecurityManager
 *
 * @package CleverConnectors\AppBundle\Model\OpenSourcePlugins
 */
class OpenSourcePluginsSecurityManager
{

    /**
     * @var ObjectRepository|SystemInstallRepository
     */
    private $systemInstallRepository;

    /**
     * @var SystemInstall|null
     */
    private $systemInstall = NULL;

    /**
     * SystemInstallSecurity constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
    }

    /**
     * @param array $headers
     *
     * @throws Exception
     */
    public function checkSystemInstall(array $headers): void
    {
        $this->systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($headers);
    }

    /**
     * @return SystemInstall
     * @throws CleverConnectorsException
     */
    public function getSystemInstall(): SystemInstall
    {
        if (!$this->systemInstall) {
            throw new CleverConnectorsException(
                'User has no installed system.',
                CleverConnectorsException::SYSTEM_NOT_INSTALLED
            );
        }

        return $this->systemInstall;
    }

}