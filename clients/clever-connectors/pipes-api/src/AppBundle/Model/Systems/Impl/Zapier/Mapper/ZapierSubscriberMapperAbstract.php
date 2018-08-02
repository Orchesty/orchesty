<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 10/31/17
 * Time: 12:30 PM
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class ZapierSubscriberMapperAbstract
 *
 * @package AppBundle\Model\Systems\Impl\Zapier\Mapper
 */
class ZapierSubscriberMapperAbstract implements CustomNodeInterface
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
     * ZapierSubscriberMapperAbstract constructor.
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
        $data = json_decode($dto->getData(), TRUE);

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
            $subscriber->setLastName($data['last_name'] ?? '');
        }

        if (array_key_exists('id', $data)) {
            $subscriber->setForeignId($data['id']);
        }

        return $dto->setData(json_encode($subscriber->toArray()));
    }

}