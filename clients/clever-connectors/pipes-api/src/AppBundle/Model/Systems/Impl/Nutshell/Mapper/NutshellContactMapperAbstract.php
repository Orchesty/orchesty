<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\Mapper;

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
use Nette\Utils\Strings;

/**
 * Class NutshellContactMapperAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\Mapper
 */
abstract class NutshellContactMapperAbstract implements CustomNodeInterface
{

    protected const CREATE = 'create';
    protected const UPDATE = 'update';
    protected const DELETE = 'delete';

    /**
     * @var string
     */
    protected $action;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * NutshellContactMapperAbstract constructor.
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

        if (!isset($data['payloads'][0]['emails'][0]['value'])) {
            throw new CleverConnectorsException(
                'Missing required email field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $subscriber = (new CMSubscriber())
            ->setEmail($data['payloads'][0]['emails'][0]['value']);

        if ($this->action === self::CREATE) {
            $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
            $lists         = $systemInstall->getSettings()[SystemInstall::SELECT_LIST] ?? NULL;

            if ($lists) {
                $subscriber->setLists([$lists]);
            }
        }

        if (isset($data['payloads'][0]['name'])) {
            $name     = $data['payloads'][0]['name'];
            $position = strrpos($name, ' ');

            if ($position !== FALSE) {
                $subscriber
                    ->setFirstName(Strings::substring($name, 0, $position))
                    ->setLastName(Strings::substring($name, $position + 1));
            } else {
                $subscriber->setLastName($name);
            }

        }

        if (isset($data['payloads'][0]['id'])) {
            $subscriber->setForeignId(explode('-', $data['payloads'][0]['id'])[0]);
        }

        return $dto->setData(Json::encode($subscriber->toArray()));
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    protected function processSync(ProcessDto $dto): ProcessDto
    {
        $data = Json::decode($dto->getData(), TRUE);
        if (!isset($data['result']['email']['--primary'])) {
            throw new CleverConnectorsException(
                'Missing required email field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $subscriber = (new CMSubscriber())
            ->setEmail($data['result']['email']['--primary']);

        if ($this->action === self::UPDATE) {
            $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
            $lists         = $systemInstall->getSettings()[SystemInstall::SELECT_LIST] ?? NULL;

            if ($lists) {
                $subscriber->setLists([$lists]);
            }
        }

        if (isset($data['result']['name']['givenName'])) {
            $subscriber->setFirstName($data['result']['name']['givenName']);
        }

        if (isset($data['result']['name']['familyName'])) {
            $subscriber->setLastName($data['result']['name']['familyName']);
        }

        if (isset($data['result']['id'])) {
            $subscriber->setForeignId($data['result']['id']);
        }

        return $dto->setData(Json::encode($subscriber->toArray()));
    }

    /**
     * @param ProcessDto $dto
     * @param string     $action
     *
     * @return ProcessDto
     */
    protected function getNeededAction(ProcessDto $dto, string $action): ProcessDto
    {
        $data = Json::decode($dto->getData(), TRUE);

        if ($data['events'][0]['payloadType'] !== 'contacts' || $data['events'][0]['action'] !== $action) {
            return HeadersUtils::setStopHeaderToDto($dto, sprintf(
                'Data does not contains contact %s event',
                $action
            ));
        }

        return $dto;
    }

}