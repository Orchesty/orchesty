<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Mapper;

use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

/**
 * Class BasecrmUpdateContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Mapper
 */
class BasecrmUpdateContactMapper implements CustomNodeInterface, LoggerAwareInterface
{

    use LoggerTrait;

    /**
     * BasecrmUpdateContactMapper constructor.
     */
    public function __construct()
    {
        $this->logger = new NullLogger();
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

        if (empty($data[CleverFieldsEnum::FOREIGN_ID] ?? '')) {
            throw new CleverConnectorsException('Missing id in data, BaseCRM updateContactConnector',
                CleverConnectorsException::MISSING_DATA);
        }

        $key = CMHeaders::get(CMHeaders::CM_EVENT_TYPE, $dto->getHeaders()) ?? '';

        return $dto->setData(json_encode([
            'id'   => $data[CleverFieldsEnum::FOREIGN_ID],
            'body' => json_encode([
                'data' => [
                    'custom_fields' => [
                        CleverCustomKeysEnum::getFromType($key) => TRUE,
                    ],
                ],
            ]),
        ]));
    }

}