<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Utils;

use Hanaboso\CommonsBundle\Process\BatchProcessDto;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\Utils\String\Json;
use PhpAmqpLib\Message\AMQPMessage;
use RabbitMqBundle\Utils\Message;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ProcessDtoFactory
 *
 * @package Hanaboso\PipesPhpSdk\Utils
 */
final class ProcessDtoFactory
{

    public const BODY    = 'body';
    public const HEADERS = 'headers';

    /**
     * @param Request $request
     *
     * @return ProcessDto
     */
    public static function createFromRequest(Request $request): ProcessDto
    {
        $data = Json::decode($request->getContent());

        return self::createDto($data[self::BODY], $data[self::HEADERS]);
    }

    /**
     * @param Request $request
     *
     * @return BatchProcessDto
     */
    public static function createBatchFromRequest(Request $request): BatchProcessDto
    {
        $data = Json::decode($request->getContent());

        return self::createBatchDto($data[self::BODY], $data[self::HEADERS]);
    }

    /**
     * @param AMQPMessage $message
     *
     * @return ProcessDto
     */
    public static function createFromMessage(AMQPMessage $message): ProcessDto
    {
        return self::createDto(Message::getBody($message), Message::getHeaders($message));
    }

    /**
     * ---------------------------------------- HELPERS -----------------------------------------
     */

    /**
     * @param string  $content
     * @param mixed[] $headers
     *
     * @return ProcessDto
     */
    private static function createDto(string $content, array $headers): ProcessDto
    {
        $dto = new ProcessDto();
        $dto
            ->setData($content)
            ->setHeaders($headers)
            ->setSuccessProcess();

        return $dto;
    }

    /**
     * @param string  $content
     * @param mixed[] $headers
     *
     * @return BatchProcessDto
     */
    private static function createBatchDto(string $content, array $headers): BatchProcessDto
    {
        $dto = new BatchProcessDto();
        $dto
            ->setBridgeData($content)
            ->setHeaders($headers)
            ->setSuccessProcess();

        return $dto;
    }

}
