<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class ZendeskCreateUserMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Mapper
 */
class ZendeskCreateUserMapper implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);
        $name = $data[CleverFieldsEnum::FIRST_NAME] === '' ? '' : $data[CleverFieldsEnum::FIRST_NAME] . ' ';
        $name .= $data[CleverFieldsEnum::LAST_NAME];

        $contact = [
            'user' => [
                'email' => $data[CleverFieldsEnum::EMAIL] ?? '',
                'name'  => $name,
            ],
        ];

        return $dto->setData(json_encode($contact));
    }

}