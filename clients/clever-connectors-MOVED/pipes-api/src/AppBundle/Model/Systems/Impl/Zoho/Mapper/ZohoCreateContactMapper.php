<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class ZohoCreateContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zoho\Mapper
 */
class ZohoCreateContactMapper implements CustomNodeInterface
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

        if (!is_array($data) || !array_key_exists(CleverFieldsEnum::EMAIL, $data)) {
            throw new CleverConnectorsException(
                'Missing data or required field email',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return $dto->setData(json_encode([
            'xml' => sprintf(
                "<Contacts><row no='1'><FL val='Email'>%s</FL><FL val='First Name'>%s</FL><FL val='Last Name'>%s</FL></row></Contacts>",
                $data[CleverFieldsEnum::EMAIL],
                $data[CleverFieldsEnum::FIRST_NAME] ?? '',
                $data[CleverFieldsEnum::LAST_NAME] ?? ''
            ),
        ]));
    }

}