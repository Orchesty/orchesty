<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/15/17
 * Time: 3:06 PM
 */

namespace Hanaboso\PipesFramework\HbPFAuthorizationBundle\DocumentListener;

use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Event\PreFlushEventArgs;
use Hanaboso\PipesFramework\Authorizations\Document\AuthorizationToken;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;

/**
 * Class AuthorizationTokenListener
 *
 * @package Hanaboso\PipesFramework\Authorizations\EntityListener
 */
class DocumentListener
{

    /**
     * @var CryptManager
     */
    private $cryptManager;

    /**
     * DocumentListener constructor.
     *
     * @param CryptManager $cryptManager
     */
    function __construct(CryptManager $cryptManager)
    {
        $this->cryptManager = $cryptManager;
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

            /** @var AuthorizationToken $document */
            foreach ($documents as $document) {
                if ($this->isAuthorizationToken($document)) {
                    $document->setData($this->cryptManager->encrypt($document->getData()));
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

        if ($this->isAuthorizationToken($document)) {
            $document->setData($this->cryptManager->decrypt($document->getData()));
        }
    }

    /**
     * @param mixed $document
     *
     * @return bool
     */
    private function isAuthorizationToken($document): bool
    {
        return $document instanceof AuthorizationToken;
    }

}