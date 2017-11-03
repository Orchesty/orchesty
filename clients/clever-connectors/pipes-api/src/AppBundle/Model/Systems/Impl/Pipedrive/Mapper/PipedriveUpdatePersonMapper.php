<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class PipedriveUpdatePersonMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Mapper
 */
class PipedriveUpdatePersonMapper implements CustomNodeInterface
{

    /**
     * @var ObjectRepository|SystemInstallRepository
     */
    protected $systemInstallRepository;

    /**
     * PipedriveCMPersonMapper constructor.
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
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());

        $data = json_decode($dto->getData(), TRUE);

        $field = CMHeaders::get(CMHeaders::CM_EVENT_TYPE, $dto->getHeaders()) ?? '';
        $hash  = $this->getHash(CleverCustomKeysEnum::getFromType($field), $systemInstall);

        return $dto->setData(json_encode([
            'id'   => $data[CleverFieldsEnum::FOREIGN_ID],
            'body' => json_encode([
                $hash => 'true',
            ]),
        ]));
    }

    /**
     * @param string        $key
     * @param SystemInstall $systemInstall
     *
     * @return string
     * @throws SystemException
     */
    private function getHash(string $key, SystemInstall $systemInstall): string
    {
        $hash = $systemInstall->getSettings()[$key] ?? '';
        if (empty($hash)) {
            throw new SystemException('Missing custom_field\'s hash in Pipedrive systemInstall.',
                SystemException::MISSING_DATA);
        }

        return $hash;
    }

}