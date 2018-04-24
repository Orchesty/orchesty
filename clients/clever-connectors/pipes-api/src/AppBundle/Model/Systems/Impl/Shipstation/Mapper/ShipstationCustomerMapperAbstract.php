<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shipstation\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Nette\Utils\Json;
use Nette\Utils\Strings;

/**
 * Class ShipstationCustomerMapperAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shipstation\Mapper
 */
class ShipstationCustomerMapperAbstract implements CustomNodeInterface
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
     * ShipstationCustomerMapperAbstract constructor.
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
        $data = Json::decode($dto->getData(), TRUE);

        if (!array_key_exists('email', $data)) {
            throw new CleverConnectorsException(
                'Missing required email field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $subscriber = (new CMSubscriber())
            ->setEmail($data['email']);

        if ($this->action === self::CREATE) {
            $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
            $lists         = $systemInstall->getSettings()[SystemInstall::SELECT_LIST] ?? NULL;

            if ($lists) {
                $subscriber->setLists([$lists]);
            }
        }

        if (array_key_exists('name', $data)) {
            $position = strrpos($data['name'], ' ');
            if ($position !== FALSE) {
                $subscriber
                    ->setFirstName(Strings::substring($data['name'], 0, $position))
                    ->setLastName(Strings::substring($data['name'], $position + 1));
            } else {
                $subscriber->setLastName($data['name']);
            }
        }

        if (array_key_exists('customerId', $data)) {
            $subscriber->setForeignId($data['customerId']);
        }

        return $dto->setData(Json::encode($subscriber->toArray()));
    }

}