<?php declare(strict_types=1);

namespace Demo\CustomNode;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;
use Hanaboso\CommonsBundle\Utils\Json;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract;

/**
 * Class DataGeneratorConnector
 *
 * @package Demo\CustomNode
 */
final class DataGeneratorConnector extends CustomNodeAbstract
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws Exception
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = Json::decode($dto->getData());
        $key  = DateTimeUtils::getUtcDateTime()->format('d. m. Y H:i:s');

        $data['generator'][$key] = [
            password_hash($key, PASSWORD_ARGON2I),
            password_hash($key, PASSWORD_ARGON2I),
            password_hash($key, PASSWORD_ARGON2I),
        ];

        return $dto->setData(Json::encode($data));
    }

}
