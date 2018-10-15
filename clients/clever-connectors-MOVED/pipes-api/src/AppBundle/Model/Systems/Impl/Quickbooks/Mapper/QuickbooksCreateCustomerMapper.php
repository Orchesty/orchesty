<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class QuickbooksCreateCustomerMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Mapper
 */
class QuickbooksCreateCustomerMapper implements CustomNodeInterface
{

    public const FIRST_NAME = 'GivenName';
    public const LAST_NAME  = 'FamilyName';
    public const SUCCESS    = 'success';
    public const ATTEMPT    = 'attempt';

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);

        if (empty($data[CleverFieldsEnum::FIRST_NAME] ?? '') && empty($data[CleverFieldsEnum::FIRST_NAME] ?? '')) {
            throw new CleverConnectorsException(
                'Either first or last name must be filled.',
                CleverConnectorsException::MISSING_DATA
            );
        }
        if (empty($data[CleverFieldsEnum::EMAIL] ?? '')) {
            throw new CleverConnectorsException(
                'Missing email in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $customer = [
            'PrimaryEmailAddr' => [
                'Address' => $data[CleverFieldsEnum::EMAIL] ?? '',
            ],
            self::FIRST_NAME   => $data[CleverFieldsEnum::FIRST_NAME] ?? '',
            self::LAST_NAME    => $data[CleverFieldsEnum::LAST_NAME] ?? '',
        ];

        return $dto->setData(json_encode([
            'body'        => json_encode($customer),
            self::SUCCESS => FALSE,
            self::ATTEMPT => FALSE,
        ]));
    }

}