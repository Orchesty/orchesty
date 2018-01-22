<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\TestNotificationConnector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Psr\Log\LoggerAwareInterface;
use Tests\Integration\AppBundle\Model\Systems\Impl\NullSystem;

/**
 * Class CMTestNotificationConnector
 *
 * @package CleverConnectors\AppBundle\Model\CM\TestNotificationConnector
 */
class CMTestNotificationConnector implements CustomNodeInterface, LoggerAwareInterface
{

    use LoggerTrait;

    /**
     * @var SystemInterface
     */
    private $system;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * CMTestNotificationConnector constructor.
     *
     * @param DocumentManager $dm
     * @param OAuth2Provider  $provider
     */
    public function __construct(DocumentManager $dm, OAuth2Provider $provider)
    {
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->system                  = new NullSystem($provider);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $data          = $dto->getData() ? json_decode($dto->getData(), TRUE) : NULL;

        if ($data) {
            foreach ($data as $key => $value) {
                if ($value) {
                    $this->logger->info($key, self::getMessage($key, $this->system, $systemInstall));
                }
            }
        }

        return $dto;
    }

}