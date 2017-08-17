<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/15/17
 * Time: 3:06 PM
 */

namespace Hanaboso\PipesFramework\Authorizations\DocumentListener;

use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Event\PreFlushEventArgs;
use Hanaboso\PipesFramework\Authorizations\Document\Authorization;
use Hanaboso\PipesFramework\Commons\CryptService\CryptService;

/**
 * Class AuthorizationTokenListener
 *
 * @package Hanaboso\PipesFramework\Authorizations\EntityListener
 */
class DocumentListener
{

    /**
     * @var CryptService
     */
    private $cryptService;

    /**
     * AuthorizationTokenListener constructor.
     *
     * @param CryptService $cryptService
     */
    function __construct(CryptService $cryptService)
    {
        $this->cryptService = $cryptService;
    }

    /**
     * @param PreFlushEventArgs $event
     */
    public function preFlush(PreFlushEventArgs $event): void
    {
        for ($i = 0; $i <= 1; $i++) {
            $documents = $i
                ? $event->getDocumentManager()->getUnitOfWork()->getScheduledDocumentUpdates()
                : $event->getDocumentManager()->getUnitOfWork()->getScheduledDocumentInsertions();

            /** @var Authorization $document */
            foreach ($documents as $document) {
                if ($this->isAuthorizationToken($document)) {
                    $document->setEncrypted($this->cryptService->encrypt($document->getToken()));
                }
            }
        }
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function postLoad(LifecycleEventArgs $event): void
    {
        $document = $event->getDocument();

        /** @var Authorization $document */
        if ($this->isAuthorizationToken($document)) {
            $document->setToken($this->cryptService->decrypt($document->getEncrypted()));
        }
    }

    /**
     * @param mixed $document
     *
     * @return bool
     */
    private function isAuthorizationToken($document): bool
    {
        return $document instanceof Authorization;
    }

}