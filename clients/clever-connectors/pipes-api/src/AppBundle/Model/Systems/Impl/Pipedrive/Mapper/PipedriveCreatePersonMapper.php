<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\PipedriveSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class PipedriveCreatePersonMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Mapper
 */
class PipedriveCreatePersonMapper implements CustomNodeInterface, LoggerAwareInterface
{

    use LoggerTrait;

    /**
     * @var PipedriveSystem
     */
    private $system;

    /**
     * @var ObjectRepository|SystemInstallRepository
     */
    protected $systemInstallRepository;

    /**
     * PipedriveCMPersonMapper constructor.
     *
     * @param DocumentManager $dm
     * @param PipedriveSystem $system
     */
    public function __construct(DocumentManager $dm, PipedriveSystem $system)
    {
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->system                  = $system;
        $this->logger                  = new NullLogger();
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
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

        if (empty($fields['name'])) {
            $this->logError(400, $this->system, $systemInstall);
            throw new CleverConnectorsException(
                'Required either first_name or last_name.',
                CleverConnectorsException::MISSING_DATA
            );
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
     */
    private function getHash(string $key, SystemInstall $systemInstall): string
    {
        $hash = $systemInstall->getSettings()[$key] ?? '';

        return $hash;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

}