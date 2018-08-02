<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class NutshellUpdateContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\Mapper
 */
class NutshellUpdateContactMapper implements CustomNodeInterface
{

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
            || !array_key_exists(CleverFieldsEnum::FOREIGN_ID, $data)
            || !isset($data['result']['rev'])
        ) {
            throw new CleverConnectorsException(
                'Missing data or required field _foreign_id or result_rev',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return $dto->setData(json_encode([
            'jsonrpc' => '2.0',
            'id'      => 'contact',
            'method'  => 'editContact',
            'params'  => [
                'contactId' => $data[CleverFieldsEnum::FOREIGN_ID],
                'rev'       => $data['result']['rev'],
                'contact'   => [
                    'customFields' => [],
                ],
            ],
        ]));
    }

}