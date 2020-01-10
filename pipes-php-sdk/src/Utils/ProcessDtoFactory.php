<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Utils;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use PhpAmqpLib\Message\AMQPMessage;
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
        return self::createDto((string) $request->getContent(), $request->headers->all());
    }

    /**
     * @param AMQPMessage $message
     *
     * @return ProcessDto
     */
    public static function createFromMessage(AMQPMessage $message): ProcessDto
    {
        return self::createDto((string) $message->body, $message->get_properties());
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
            ->setSuccessProcess()
            ->setData($content)
            ->setHeaders($headers);

        return $dto;
    }

}