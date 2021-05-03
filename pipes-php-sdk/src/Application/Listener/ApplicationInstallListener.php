<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Listener;

use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Event\PostFlushEventArgs;
use Doctrine\ODM\MongoDB\Event\PreFlushEventArgs;
use Hanaboso\CommonsBundle\Crypt\CryptManager;
use Hanaboso\CommonsBundle\Crypt\Exceptions\CryptException;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;

/**
 * Class ApplicationInstallListener
 *
 * @package Hanaboso\PipesPhpSdk\Application\Listener
 */
final class ApplicationInstallListener
{

    /**
     * ApplicationInstallListener constructor.
     *
     * @param CryptManager $cryptManager
     */
    public function __construct(private CryptManager $cryptManager)
    {
    }

    /**
     * @param LifecycleEventArgs $args
     *
     * @throws CryptException
     */
    public function postLoad(LifecycleEventArgs $args): void
    {
        $document = $args->getDocument();
        $this->decrypt([$document]);
    }

    /**
     * @param PreFlushEventArgs $args
     *
     * @throws CryptException
     */
    public function preFlush(PreFlushEventArgs $args): void
    {
        $uof = $args->getDocumentManager()->getUnitOfWork();
        $uof->computeChangeSets();
        $this->encrypt($uof->getScheduledDocumentInsertions());
        $this->encrypt($uof->getScheduledDocumentUpdates());
        $this->encrypt($uof->getScheduledDocumentUpserts());
    }

    /**
     * @param PostFlushEventArgs $args
     *
     * @throws CryptException
     */
    public function postFlush(PostFlushEventArgs $args): void
    {
        $uof = $args->getDocumentManager()->getUnitOfWork();
        $this->decrypt($uof->getIdentityMap());
    }

    /**
     * ------------------------------- HELPERS ----------------------------------------
     */

    /**
     * @param mixed[] $documents
     *
     * @throws CryptException
     */
    private function encrypt(array $documents): void
    {
        foreach ($documents as $document) {
            if ($document instanceof ApplicationInstall) {
                $document
                    ->setEncryptedSettings($this->cryptManager->encrypt($document->getSettings()))
                    ->setSettings([]);
            }
        }
    }

    /**
     * @param mixed[] $documents
     *
     * @throws CryptException
     */
    private function decrypt(array $documents): void
    {
        foreach ($documents as $document) {
            if (is_array($document)) {
                $this->decrypt($document);
            }

            if ($document instanceof ApplicationInstall) {
                $document->setSettings(
                    !empty($document->getEncryptedSettings()) ?
                        $this->cryptManager->decrypt($document->getEncryptedSettings()) : [],
                );
            }
        }
    }

}
