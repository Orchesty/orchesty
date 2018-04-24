<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\HeadersUtils;
use Doctrine\Common\Persistence\ObjectRepository;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class ZendeskUserMapperAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Mapper
 */
class ZendeskUserMapperAbstract implements CustomNodeInterface
{

    /**
     * @var bool
     */
    protected $includeList = FALSE;

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    protected $systemInstallRepository;

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);

        if (!array_key_exists('email', $data)) {
            throw new CleverConnectorsException(
                'Missing required email field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        if ($this->omit($dto, $data)) {
            $dto = HeadersUtils::setStopHeaderToDto(
                $dto,
                'Undesired mapper branch in Zendesk topology for given item.'
            );
        } else {
            $obj = $this->createSubscriber($dto, $data);
            $dto->setData(json_encode($obj->toArray()));
        }

        return $dto;
    }

    /**
     * @param ProcessDto $dto
     * @param array      $data
     *
     * @return CMSubscriber
     */
    protected function createSubscriber(ProcessDto $dto, array $data): CMSubscriber
    {
        $obj = new CMSubscriber();
        $obj->setEmail($data['email']);

        if (array_key_exists('name', $data)) {
            $name  = $data['name'];
            $first = '';
            $last  = $name;

            $len = strpos($name, ' ');
            if ($len !== FALSE) {
                $first = substr($name, 0, $len);
                $last  = substr($name, $len + 1);
            }

            $obj->setFirstName($first);
            $obj->setLastName($last);
        }

        if (array_key_exists('id', $data)) {
            $obj->setForeignId($data['id']);
        }

        if ($this->includeList) {
            $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
            $sett          = $systemInstall->getSettings();

            if (array_key_exists(SystemInstall::SELECT_LIST, $sett)) {
                $obj->setLists([$sett[SystemInstall::SELECT_LIST]]);
            }
        }

        return $obj;
    }

    /**
     * @param ProcessDto $dto
     * @param array      $data
     *
     * @return bool
     */
    protected function omit(ProcessDto $dto, array $data): bool
    {
        return FALSE;
    }

}