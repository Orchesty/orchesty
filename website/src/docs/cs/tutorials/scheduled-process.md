---
layout: main.hbs
collection: documentation
name: Plánované spouštění procesu a stránkování zdrojových dat
parent: Tutoriály
level: 2
index: 60

lunr: true
tags: scheduled process plánovaná úloha
lang: cs
---



V [minulém návodu](/docs/cs/tutorials/batch-splitter) jsme se naučili, jak vytvořit proces, který nejprve stáhne dávku dat a poté ji rozdělí na jednotlivé části.

V tomto návodu si ukážeme, jak tento proces zjednodušit. K tomu slouží **Batch Connector**.

## Co budeme potřebovat?
- Nainstalované PIPES na svém localhostu pro vytvoření nového konektoru. Instalaci můžete provést pomocí návodu [Instalace a spuštění PIPES](/docs/cs/installation).
- Připravenou službu s implementovaným balíčkem SDK, registrovanou v PIPES pro přímou integraci. Pokud službu ještě nemáte, podívejte se na kapitolu [Jak nastavit vlastní službu s využitím SDK pro přímou integraci s PIPES](/docs/cs/tutorials/sdk-settings/).
- Aplikaci pro integraci SaaS služby HubSpot, kterou jsme připravili v rámci návodu [Jak vytvořit aplikaci s autorizací OAuth&nbsp;2](/docs/cs/tutorials/oauth2-application)


## Batch Connector
Batch Connector je speciální druh konektoru, který kombinuje stažení dávky dat a jejich rozdělení na jednotlivé části.
Pro ukázku si zkusíme stáhnout námi vytvořené kontakty z HubSpotu pomocí stránkování.

Pro vytvoření konektoru začneme stejně jako u Batch splitteru.

``` PHP 1

namespace Pipes\PhpSdk\Batch\Connector\HubSpot;

use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessActionNotSupportedTrait;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchInterface;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchTrait;
use Psr\Log\LoggerAwareInterface;

final class HubSpotListContactsConnector extends ConnectorAbstract implements BatchInterface, LoggerAwareInterface
{

    use ProcessActionNotSupportedTrait;
    use ProcessEventNotSupportedTrait;
    use BatchTrait;

    ... required methods from interface
}
```

Připravíme si metodu `processBatch` ve které získáme pomocí Aplikace HubSpot sestavený požadavek. Tento požadavek odešleme do metody `doPageLoop`.

``` PHP 2

use GuzzleHttp\Promise\PromiseInterface;
use Hanaboso\CommonsBundle\Process\ProcessDto;

...

public function processBatch(ProcessDto $dto, callable $callbackItem): PromiseInterface
    {
        $dto->addHeader(PipesHeaders::createKey(PipesHeaders::APPLICATION), $this->getApplicationKey() ?? '');
        $applicationInstall = $this->repository->findUsersAppDefaultHeaders($dto);
        $requestDto         = $this->getApplication()->getRequestDto(
            $applicationInstall,
            CurlManager::METHOD_GET,
            sprintf(self::URL, HubspotApplication::BASE_URL)
        );
        $requestDto->setDebugInfo($dto);

        return $this->doPageLoop($callbackItem, $requestDto, $applicationInstall);
    }
```

Metoda `doPageLoop` obstarává rekurzivní procházení stránek tak, abychom získali veškerá data z daného systému. Z důvodu větší rychlosti procesu zde využíváme asynchronního zpracování požadavků.
Jak můžeme vidět, požadavek na první stránku nám vrátí Promise. Na Promise reagujeme pomocí dvou funkcí. První funkce obsluhuje větěv **onFulfilled**. To je větev, kterou použijeme v případě úspěšného zpracování předchozí Promise.
Druhá **onRejected** naopak pokud předchozí Promise selže. 

Naše onFulfilled větěv obsahuje rozparsování odpovědi z HubSpotu. Pomocí metody `createSuccessMessage` rozebereme odpověď na jednotlivé kontakty. Na závěr se zeptáme, zdali jsou v HubSpotu další kontakty. Pokud ano, rekurzivně zavoláme `doPageLoop`. Nezapomeneme předat inkrementovanou stránku a offset dalšího kontaktu. 
Oproti tomu onRejected větev pak obsahuje pouze zalogování chyby a předání výjimky.

Kód pak vypadá následovně: 

``` PHP 3

private function doPageLoop(callable $callbackItem, RequestDto $dto, ApplicationInstall $install, ProcessDto $processDto, int $page = 0, int $offset = 0): PromiseInterface
{
    $uri = $this->getUri($dto, $offset);

    return $this->sender->sendAsync(RequestDto::from($dto, $uri))
        ->then(
            function (ResponseInterface $response) use (
                $dto,
                $callbackItem,
                $page,
                $install,
                $processDto
            ): PromiseInterface {
                $body = $response->getBody()->getContents();
                $data = empty($body) ? [] : Json::decode($body);
                $this->createSuccessMessage($install, $callbackItem, $data, ++$page, $processDto->getHeaders());

                if ($data['has-more'] ?? FALSE) {
                    return $this->doPageLoop(
                        $callbackItem,
                        $dto,
                        $install,
                        $processDto,
                        ++$page,
                        $data['vid-offset'] ?? 0
                    );
                } else {
                    unset($body, $data);

                    return $this->createPromise();
                }
            },
            fn(Exception $e) => $callbackItem(
                $this->batchConnectorError($e, $install, [], $processDto->getHeaders())
            )
        );
}

private function getUri(RequestDto $dto, int $offset): Uri
{
    return new Uri(
        sprintf(
            '%s?count=%s&vidOffset=%s',
            urldecode($dto->getUriString()),
            self::ITEMS_PER_PAGE,
            $offset,
        )
    );
}

private function createSuccessMessage(ApplicationInstall $install, callable $callbackItem, array $data, int $page, array $headers = []): void
{
    if (array_key_exists('contacts', $data)) {
        $contacts = $data['contacts'];
        $i        = $page * self::ITEMS_PER_PAGE;
        foreach ($contacts as $contact) {
            $successMessage = new SuccessMessage($i);
            $successMessage->setData(Json::encode($contact));
            $callbackItem($successMessage);
            $i++;
        }

        unset($data, $contacts, $i, $successMessage);
    } else {
        $this->batchConnectorError(
            new ConnectorException('Bad response data from HubSpot response. Missing "contacts".'),
            $install,
            ['data' => $data],
            $headers
        );
    }
}

private function batchConnectorError(Exception $e, ApplicationInstall $install, array $context = [], array $headers = []): SuccessMessage
{
    $context = array_merge(
        [
            'app_install' => $install->getId(),
            'user'        => $install->getUser(),
            'key'         => $install->getKey(),
        ],
        $context
    );

    $this->logger->error($e->getMessage(), array_merge($context, PipesHeaders::debugInfo($headers)));
    unset($context);

    throw $e;
}
```


Konektor nezapomene zaregistrovat jako službu:

``` YAML 4

# ./config/batch/batch.yaml
services:
    hbpf.connector.hub-spot.list-contacts:
        class: Pipes\PhpSdk\Batch\Connector\HubSpot\HubSpotListContactsConnector
        arguments:
            - '@doctrine_mongodb.odm.default_document_manager'
            - '@hbpf.transport.curl_manager'
        calls:
            - ['setApplication', ['@hbpf.application.hub-spot']]
            - ['setLogger', ['@monolog.logger.commons']]
```

## Plánovaný proces

PIPES umožňují vytvořit proces, který se bude periodicky opakovat dle nastavení ve formátu **CRON expression**.

Vytvoříme si novou Topologii, pojmenujeme si ji například **ScheduledTopology**. Přidáme si cron start event a náš vytvořený Batch connector. Jako cíl zvolíme náš user debug task.
Když vybereme cron, je možné v sidebaru nastavit, jak často chceme topologii spouštět. Nastavíme interval opakování na každou druhou minutu: `*/2 * * * *`. 
Topologie pak bude vypadat následovně:

![](/uploads/scr_scheduled/1_scheduled_topo.png "Cron topologie")

Jakmile dojde k automatickému spuštění topologie, uvidíme, že v debug user tasku přibyly záznamy.

![](/uploads/scr_scheduled/2_scheduled_topo.png "Cron topologie - výsledek")

Nyní se podívejme na seznam všech plánovaných úloh. Najdeme ho v PIPES Admin v záložce [Cron Tasks](http://127.0.0.10/ui/cron_tasks). Zde vidíme všechny plánované úlohy a jejich nastavení.

Gratulujeme, právě jste se naučili používat plánované úlohy v PIPES. V následujícím tutoriálu si ukážeme, [jak integrovat službu s využitím webhooks](/docs/cs/tutorials/webhooks).
