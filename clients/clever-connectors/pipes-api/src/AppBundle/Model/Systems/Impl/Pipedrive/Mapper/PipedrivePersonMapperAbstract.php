<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\HeadersUtils;
use Doctrine\Common\Persistence\ObjectRepository;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class PipedrivePersonMapperAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Mapper
 */
abstract class PipedrivePersonMapperAbstract implements CustomNodeInterface
{

    protected const OMMIT = '__ommit';

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
        $data = $this->getInnerData($data);

        if ($data === self::OMMIT) {
            return HeadersUtils::setStopHeaderToDto($dto, sprintf(
                    'Undesired updated webhook branch, Pipedrive.')
            );
        } else {
            if (!array_key_exists('email', $data)
                || empty($data['email'][0])
                || !array_key_exists('value', $data['email'][0])
            ) {
                throw new CleverConnectorsException(
                    'Missing required email field in data.',
                    CleverConnectorsException::MISSING_DATA
                );
            }

            $obj = new CMSubscriber();
            $obj->setEmail($data['email'][0]['value']);

            if (array_key_exists('first_name', $data)) {
                $obj->setFirstName($data['first_name']);
            }

            if (array_key_exists('last_name', $data)) {
                $obj->setLastName($data['last_name'] ?? '');
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

            return $dto->setData(json_encode($obj->toArray()));
        }
    }

    /**
     * @param array $data
     *
     * @return array|string
     */
    abstract protected function getInnerData(array $data);

}