<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Filter;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class FacebookaudienceFilterAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Filter
 */
abstract class FacebookaudienceFilterAbstract implements CustomNodeInterface
{

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    protected $systemInstallRepository;

    /**
     * FacebookaudienceGetAudiencesConnector constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return array
     */
    protected function getSettings(ProcessDto $dto): array
    {
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());

        return $systemInstall->getSettings();
    }

}