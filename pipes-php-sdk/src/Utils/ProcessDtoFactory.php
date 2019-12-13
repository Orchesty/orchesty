<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Utils;

use Bunny\Message;
use Hanaboso\CommonsBundle\Process\ProcessDto;
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
     * @param Message $message
     *
     * @return ProcessDto
     */
    public static function createFromMessage(Message $message): ProcessDto
    {
        return self::createDto((string) $message->content, $message->headers);
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