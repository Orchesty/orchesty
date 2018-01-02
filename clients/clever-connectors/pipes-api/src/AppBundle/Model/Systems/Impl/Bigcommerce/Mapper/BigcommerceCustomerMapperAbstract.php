<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Nette\Utils\Json;

/**
 * Class BigcommerceCustomerMapperAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Mapper
 */
abstract class BigcommerceCustomerMapperAbstract implements CustomNodeInterface
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
     * BigcommerceCustomerMapperAbstract constructor.
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

        if (array_key_exists('first_name', $data)) {
            $subscriber->setFirstName($data['first_name']);
        }

        if (array_key_exists('last_name', $data)) {
            $subscriber->setLastName($data['last_name']);
        }

        if (array_key_exists('id', $data)) {
            $subscriber->setForeignId($data['id']);
        }

        return $dto->setData(Json::encode($subscriber->toArray()));
    }

}