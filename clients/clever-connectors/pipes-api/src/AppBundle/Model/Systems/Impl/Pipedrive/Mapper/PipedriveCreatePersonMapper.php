<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class PipedriveCreatePersonMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Mapper
 */
class PipedriveCreatePersonMapper implements CustomNodeInterface
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
    function __construct(DocumentManager $dm)
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

        $fields = [];

        $data = json_decode($dto->getData(), TRUE);
        if (!empty($data[CleverFieldsEnum::EMAIL] ?? '')) {
            $fields['email'] = $data[CleverFieldsEnum::EMAIL];
        }
        if (!empty($data[CleverFieldsEnum::FIRST_NAME] ?? '')) {
            $fields['name'] = $data[CleverFieldsEnum::FIRST_NAME];
        }
        if (!empty($data[CleverFieldsEnum::LAST_NAME] ?? '')) {
            if (!empty($fields['name']) ?? '') {
                $fields['name'] .= ' ';
            }
            $fields['name'] .= $data[CleverFieldsEnum::LAST_NAME];
        }

        $unHash   = $this->getHash(CleverCustomKeysEnum::UNSUBSCRIBE, $systemInstall);
        $hardHash = $this->getHash(CleverCustomKeysEnum::HARD_BOUNCE, $systemInstall);

        if (!empty($unHash)) {
            $fields[$unHash] = 'false';
        }
        if (!empty($hardHash)) {
            $fields[$hardHash] = 'false';
        }

        return $dto->setData(json_encode($fields));
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

        return $hash;
    }

}