<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\BasecrmSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

/**
 * Class BasecrmCreateContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Mapper
 */
class BasecrmCreateContactMapper implements CustomNodeInterface, LoggerAwareInterface
{

    use LoggerTrait;

    /**
     * @var BasecrmSystem
     */
    private $system;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * BasecrmCreateContactMapper constructor.
     *
     * @param BasecrmSystem   $system
     * @param DocumentManager $dm
     */
    public function __construct(BasecrmSystem $system, DocumentManager $dm)
    {
        $this->system                  = $system;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->logger                  = new NullLogger();
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);

        if (empty($data['last_name'] ?? '')) {
            $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
            $this->logError(400, $this->system, $systemInstall);
        }

        $contact = [
            'data' => [
                'email'      => $data[CleverFieldsEnum::EMAIL] ?? '',
                'first_name' => $data[CleverFieldsEnum::FIRST_NAME] ?? '',
                'last_name'  => $data[CleverFieldsEnum::LAST_NAME] ?? '',
            ],
        ];

        return $dto->setData(json_encode($contact));
    }

}