<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 26.10.17
 * Time: 7:58
 */

namespace CleverConnectors\AppBundle\Utils;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Traits\StaticTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class InnerRequestUtils
 *
 * @package CleverConnectors\AppBundle\Utils
 */
final class InnerRequestUtils
{

    use StaticTrait;

    /**
     * @param SystemInstall $systemInstall
     * @param mixed         $data
     *
     * @return Request
     */
    public static function getRequest(SystemInstall $systemInstall, $data): Request
    {
        $request = new Request([], [], [], [], [], [], json_encode($data));
        $request->headers->set(CMHeaders::createKey(CMHeaders::GUID), $systemInstall->getUser());
        $request->headers->set(CMHeaders::createKey(CMHeaders::SYSTEM_KEY), $systemInstall->getSystem());
        $request->headers->set(CMHeaders::createKey(CMHeaders::TOKEN), $systemInstall->getToken());

        return $request;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param Request       $request
     *
     * @return Request
     */
    public static function addCMHeaders(SystemInstall $systemInstall, Request $request): Request
    {
        $request->headers->set(CMHeaders::createKey(CMHeaders::GUID), $systemInstall->getUser());
        $request->headers->set(CMHeaders::createKey(CMHeaders::SYSTEM_KEY), $systemInstall->getSystem());
        $request->headers->set(CMHeaders::createKey(CMHeaders::TOKEN), $systemInstall->getToken());

        return $request;
    }

}