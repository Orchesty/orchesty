<?php declare(strict_types=1);

namespace Demo\CustomNode;

use DateTime;
use DateTimeZone;
use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
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
        $data = json_decode($dto->getData(), TRUE, 512, JSON_THROW_ON_ERROR);
        $key  = (new DateTime('NOW', new DateTimeZone('UTC')))->format('d. m. Y H:i:s');

        $data['generator'][$key] = [
            password_hash($key, PASSWORD_ARGON2I),
            password_hash($key, PASSWORD_ARGON2I),
            password_hash($key, PASSWORD_ARGON2I),
        ];

        return $dto->setData(json_encode($data, JSON_THROW_ON_ERROR));
    }

}
