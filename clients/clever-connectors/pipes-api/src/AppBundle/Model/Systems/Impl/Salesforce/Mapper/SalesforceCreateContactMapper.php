<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Nette\Utils\Json;

/**
 * Class SalesforceCreateContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\Mapper
 */
class SalesforceCreateContactMapper implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = Json::decode($dto->getData(), TRUE);

        if (!is_array($data) || !array_key_exists(CleverFieldsEnum::EMAIL, $data)) {
            throw new CleverConnectorsException(
                'Missing data or required field email',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return $dto->setData(Json::encode([
            'email'     => $data[CleverFieldsEnum::EMAIL],
            'firstName' => $data[CleverFieldsEnum::FIRST_NAME] ?? '',
            'lastName'  => $data[CleverFieldsEnum::LAST_NAME] ?? '',
        ]));
    }

}