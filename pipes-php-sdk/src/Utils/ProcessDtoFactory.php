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

    /**
     * @param Request $request
     *
     * @return ProcessDto
     */
    public static function createFromRequest(Request $request): ProcessDto
    {
        return self::createDto($request->getContent(), $request->headers->all());
    }

    /**
     * @param Request $request
     *
     * @return BatchProcessDto
     */
    public static function createBatchFromRequest(Request $request): BatchProcessDto
    {
        return self::createBatchDto([$request->getContent()], $request->headers->all());
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
     * @param mixed[] $content
     * @param mixed[] $headers
     *
     * @return BatchProcessDto
     */
    private static function createBatchDto(array $content, array $headers): BatchProcessDto
    {
        $dto = new BatchProcessDto();
        $dto
            ->setBridgeData(Json::encode($content))
            ->setHeaders($headers)
            ->setSuccessProcess();

        return $dto;
    }

}
