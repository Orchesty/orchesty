<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\HeadersUtils;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Nette\Utils\Json;

/**
 * Class ZohoContactMapperAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Mapper
 */
abstract class ZohoContactMapperAbstract implements CustomNodeInterface
{

    protected const CREATE = 'create';
    protected const UPDATE = 'update';

    /**
     * @var string
     */
    protected $action;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * ZohoContactMapperAbstract constructor.
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
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);

        if (!is_array($data)
            || !array_key_exists('FL', $data)
            || empty($data['FL'])
        ) {
            throw new CleverConnectorsException(
                'Malformed or missing data in ZOHO update mapper.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $datas   = [];
        $desired = ['First Name', 'Last Name', 'Email', 'CONTACTID'];
        $search  = 0;

        foreach ($data['FL'] as $row) {
            if (array_key_exists('val', $row)
                && array_key_exists('content', $row)
                && in_array($row['val'], $desired)
            ) {
                $datas[$row['val']] = $row['content'];
                if (++$search === 4) {
                    break;
                }
            }
        }

        if (!array_key_exists('Email', $datas)
            || !array_key_exists('CONTACTID', $datas)
        ) {
            throw new CleverConnectorsException(
                'Missing required email/id field in data, ZOHO update mapper.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $obj = new CMSubscriber();
        $obj->setEmail($datas['Email'])
            ->setForeignId($datas['CONTACTID'] ?? '')
            ->setFirstName($datas['First Name'] ?? '')
            ->setLastName($datas['Last Name'] ?? '');

        if ($this->action === self::CREATE) {
            $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
            $lists         = $systemInstall->getSettings()[SystemInstall::SELECT_LIST] ?? NULL;

            if ($lists) {
                $obj->setLists([$lists]);
            }
        }

        return $dto->setData(json_encode($obj->toArray()));
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    protected function getNeededAction(ProcessDto $dto): ProcessDto
    {
        $data = Json::decode($dto->getData(), TRUE);

        foreach ($data['FL'] as $value) {
            if ($value['val'] === 'Created Time') {
                $created = $value['content'];
            }

            if ($value['val'] === 'Modified Time') {
                $updated = $value['content'];
            }
        }

        $isCreated = ($created ?? NULL) === ($updated ?? NULL);

        if ($this->action === self::CREATE && !$isCreated || $this->action === self::UPDATE && $isCreated) {
            return HeadersUtils::setStopHeaderToDto($dto, sprintf(
                'Data does not contains contact %s event',
                $this->action
            ));
        }

        return $dto;
    }

}