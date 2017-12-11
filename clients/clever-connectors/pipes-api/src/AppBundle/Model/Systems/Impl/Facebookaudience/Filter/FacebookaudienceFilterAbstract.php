<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\Filter;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
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

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    protected function setHeadersToStop(ProcessDto $dto): ProcessDto
    {
        $headers       = $dto->getHeaders();
        $key           = CMHeaders::createKey(CMHeaders::RESULT_CODE);
        $headers[$key] = 1003;
        $dto->setHeaders($headers);

        return $dto;
    }

}