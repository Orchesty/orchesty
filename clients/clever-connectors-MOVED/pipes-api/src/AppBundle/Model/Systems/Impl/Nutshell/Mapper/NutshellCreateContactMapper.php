<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class NutshellCreateContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\Mapper
 */
class NutshellCreateContactMapper implements CustomNodeInterface
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
            'jsonrpc' => '2.0',
            'id'      => 'contact',
            'method'  => 'newContact',
            'params'  => [
                'contact' => [
                    'name'  => [
                        'givenName'  => $data[CleverFieldsEnum::FIRST_NAME] ?? '',
                        'familyName' => $data[CleverFieldsEnum::LAST_NAME] ?? '',
                    ],
                    'email' => [
                        $data[CleverFieldsEnum::EMAIL],
                    ],
                ],
            ],
        ]));
    }

}