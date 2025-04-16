<?php declare(strict_types=1);

namespace Demo\CustomNode;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\CommonNodeAbstract;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\String\Json;

/**
 * Class DataGeneratorConnector
 *
 * @package Demo\CustomNode
 */
final class DataGeneratorConnector extends CommonNodeAbstract
{

    public const string NAME = 'data-generator-connector';

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws Exception
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $data = Json::decode($dto->getData());
        $key  = DateTimeUtils::getUtcDateTime()->format('d. m. Y H:i:s');

        $data['generator'][$key] = [
            password_hash($key, PASSWORD_ARGON2I),
            password_hash($key, PASSWORD_ARGON2I),
            password_hash($key, PASSWORD_ARGON2I),
        ];

        return $dto->setJsonData($data);
    }

}
