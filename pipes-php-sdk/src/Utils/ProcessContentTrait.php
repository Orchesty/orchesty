<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Utils;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessExceptionTrait;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage;
use Hanaboso\Utils\String\Json;
use JsonException;

/**
 * Trait ProcessContentTrait
 *
 * @package Hanaboso\PipesPhpSdk\Utils
 */
trait ProcessContentTrait
{

    use ProcessExceptionTrait;

    /**
     * @param ProcessDto|SuccessMessage $dto
     * @param string                    $key
     * @param mixed[]                   $contents
     * @param bool                      $throw
     *
     * @return mixed
     * @throws ConnectorException
     */
    protected function getContentByKey(
        ProcessDto|SuccessMessage $dto,
        string $key,
        array $contents = [],
        bool $throw = TRUE,
    ): mixed
    {
        $contents = $contents ?: Json::decode($dto->getData());
        $content  = $this->getByKey($contents, $key);

        if (!$content && $throw) {
            throw $this->createMissingContentException($key);
        }

        return $content;
    }

    /**
     * @param ProcessDto|SuccessMessage $dto
     * @param mixed[]                   $parameters
     *
     * @return mixed[]
     * @throws ConnectorException
     * @throws JsonException
     */
    protected function checkRequiredContent(ProcessDto|SuccessMessage $dto, array $parameters): array
    {
        $data = Json::decode($dto->getData());

        foreach ($parameters as $parameter) {
            $this->getContentByKey($dto, $parameter, $data);
        }

        return $data;
    }

    /**
     * @param mixed[] $array
     * @param string  $key
     *
     * @return mixed
     */
    private function getByKey(array &$array, string $key): mixed
    {
        if (!str_contains($key, '.')) {
            return $array[$key] ?? NULL;
        }

        foreach (explode('.', $key) as $innerKey) {
            if (!isset($array[$innerKey])) {
                return NULL;
            }

            $array = &$array[$innerKey];
        }

        return $array;
    }

}
