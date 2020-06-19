---
layout: main.hbs
collection: documentation
name: Jak použít batch splitter v dávkovém procesu
parent: Tutorials
level: 2
index: 60

lunr: true
tags: batch splitter
---

V [minulém návodu](/docs/cs/tutorials/oauth2-application) jsme sestavili proces, který importoval dávku kontaktů do aplikace HubSpot. V praxi se ale velice často setkáme potřebou dávku dat rozdělit na jednotlivé entity. V tomto návodu si ukážeme, jak v takovém případě použít prvek **Batch splitter**. Ten očekává na vstupu pole dat. Výstupem je potom instance procesu pro každý objekt pole. 

Pro náš návod upravíme proces importu kontaktů do HubSpot, který jsme vytvořili v [předchozím návodu](/docs/cs/tutorials/oauth2-application) tak, že místo endpointu pro zpracování dávky použijeme endpoint pro vložení samostatného kontaktu (viz. https://developers.hubspot.com/docs/methods/contacts/create_contact).

## Co budete potřebovat?
- Pro vytvoření nového konektoru předpokládáme, že máte nainstalované PIPES na svém localhostu. Pokud ne, instalaci můžete provézt s pomocí návodu [Instalace a spuštění PIPES](/docs/cs/installation)  
- Připravenou službu s implementovaným balíčkem SDK, registrovanou v PIPES pro přímou integraci. Pokud službu ještě nemáte, podívejte se na kapitolu Jak nastavit vlastní službu s využitím SDK pro přímou integraci s PIPES.
- Konektor na získání testovacích dat, připravený v rámci návodu [Jak vytvořit konektor pro volání REST API](/docs/cs/tutorials/basic-connector  "Jak vytvořit konektor pro volání REST API").
- Aplikaci pro integraci SaaS služby HubSpot, kterou jsme připravili v rámci návodu [Jak vytvořit aplikaci s autorizací OAuth 2](/docs/cs/tutorials/oauth2-application)

## Úprava topologie procesu
Abychom správně rozuměli tomu, jak bude náš upravený proces vypadat, upravíme nejprve topologii procesu pro náš nový záměr. Přihlásíme se do aplikace PIPES Admin a otevřeme náš testovací proces, vytvořený v rámci návodu [Jak vytvořit aplikaci s autorizací OAuth 2](/docs/cs/tutorials/oauth2-application). Proces vypadá následovně:

![](/uploads/scr_batch_splitter/1_batch_splitter_orgin.png "HubSpot - proces")

Můžeme vytvořit i zcela nový proces, ale můžeme klidně vytvořit kopii v akčním menu topologie příkazem **Clone**. 

![](/uploads/scr_batch_splitter/2_batch_splitter_clone.png "Klonování procesu")

Topologii upravíme následovně:
1. Odstraníme stávající mapper a konektor pro vložení dávky kontaktů do HubSpot.
2. Vložíme za konektor pro získání testovací dávky kontaktů prvek **Batch splitter**.
3. Za prvek Batch splitter vložíme **Custom action** pro vytvoření nového mapperu.
4. Nakonec přidáme **Connector**, který bude volat HubSpot endpoint pro vytvoření kontaktu.

![](/uploads/scr_batch_splitter/3_batch_splitter_empty_topology.png "Nová topologie s Batch Splitterem")

Nyní zbývá vytvořit scripty jednotlivých uzlů.

## Batch splitter
V našem projektu vytvoříme třídu splitteru.

``` PHP 1

namespace Pipes\PhpSdk\Batch\Splitter;

use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessActionNotSupportedTrait;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\BatchInterface;

final class UsersBatchSplitter extends ConnectorAbstract implements BatchInterface
{

    use ProcessActionNotSupportedTrait;
    use ProcessEventNotSupportedTrait;

    ... required methods from interface
}
```

Splitteru je potřeba definovat unikátní identifikátor service. k tomu slouží metoda `getId`.

``` PHP 2

public function getId(): string 
{
    return 'user-batch-splitter';
}
```

Posledním krokem je implementace samotné metody `processBatch`. Ta slouží k rozdělení dávky na jednotlivé části.

``` PHP 3

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\RabbitMq\Impl\Batch\SuccessMessage;
use Hanaboso\Utils\String\Json;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;

...

public function processBatch(ProcessDto $dto, LoopInterface $loop, callable $callbackItem): PromiseInterface
{
    $users = $this->getJsonContent($dto);

    for ($i = 0; $i < count($users); $i++) {
        $message = new SuccessMessage($i);
        $message->setData(Json::encode($users[$i]));

        $callbackItem($message);
    }

    return resolve();
}
```

Nyní stačí náš splitter jen zaregistrovat jako servicu. To uděláme následovně:

``` YAML 4

# ./config/batch/batch.yaml
services:
    hbpf.connector.user-batch-splitter:
        class: Pipes\PhpSdk\Batch\Splitter\UsersBatchSplitter
```

## Mapper
Budeme mapovat stejné objekty, jako v minulém návodu. Pouze tentokrát nepůjde o pole objektů, ale o samostatný objekt.

``` PHP 5

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract;
use Hanaboso\Utils\String\Json;
use JsonException;

final class HubSpotCreateContactMapper extends CustomNodeAbstract
{

    public function process(ProcessDto $dto): ProcessDto
    {
        $data = Json::decode($dto->getData());
        if (!isset($data['name'], $data['email'], $data['phone'])) {
            throw new ConnectorException('Some data is missing. Keys [name, email, phone] is required.');
        }

        $name = explode(' ', $data['name']);
        $body = [
            'properties' => [
                [
                    'property' => 'email',
                    'value'    => $data['email'],
                ],
                [
                    'property' => 'firstname',
                    'value'    => $name[0],
                ],
                [
                    'property' => 'lastname',
                    'value'    => $name[1] ?? '',
                ],
                [
                    'property' => 'phone',
                    'value'    => $data['phone'],
                ],
            ],
        ];

        return $dto->setData(Json::encode($body));
    }
}
```

Nezapomeneme zaregistrovat mapper jako službu.

``` YAML 6

# ./config/custom_node/custom_node.yaml
services:
    hbpf.custom_node.hub-spot.create-contact-mapper:
        class: Pipes\PhpSdk\Mapper\HubSpot\HubSpotCreateContactMapper
```

## Connector
Třída nového konektoru bude velmi podobná konektoru, který jsme postavili pro vložení dávky kontaktů. Data nám už připravil mapper, takže rozdíl bude opravdu jen v endpointu, který voláme. Celá třída bude vypadat následovně:

``` PHP 7

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\String\Json;
use Pipes\PhpSdk\Application\HubSpotApplication;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class HubSpotCreateContactConnector extends ConnectorAbstract implements LoggerAwareInterface
{

    use ProcessEventNotSupportedTrait;

    private CurlManager $sender;

    private ApplicationInstallRepository $repository;

    private LoggerInterface $logger;

    public function __construct(DocumentManager $dm, CurlManager $sender)
    {
        $this->sender     = $sender;
        $this->repository = $dm->getRepository(ApplicationInstall::class);
        $this->logger     = new NullLogger();
    }

    public function getId(): string
    {
        return 'hub-spot.create-contact';
    }

    public function setLogger(LoggerInterface $logger): HubSpotCreateContactConnector
    {
        $this->logger = $logger;

        return $this;
    }

    public function processAction(ProcessDto $dto): ProcessDto
    {
        $applicationInstall = $this->repository->findUsersAppDefaultHeaders($dto);
        $body               = $this->getJsonContent($dto);

        try {
            $response = $this->sender->send(
                $this->getApplication()->getRequestDto(
                    $applicationInstall,
                    CurlManager::METHOD_POST,
                    sprintf('%s/contacts/v1/contact', HubspotApplication::BASE_URL),
                    Json::encode($body)
                )
            );
            $message  = $response->getJsonBody()['validationResults'][0]['message'] ?? NULL;
            $this->evaluateStatusCode($response->getStatusCode(), $dto, $message);

            if ($response->getStatusCode() === 409) {
                $parsed = $response->getJsonBody();
                $this->logger->error(
                    sprintf('Contact "%s" already exist.', $parsed['identityProfile']['identity'][0]['value'] ?? ''),
                    ['Response' => $response->getBody()]
                );
            }

            $dto->setData($response->getBody());
        } catch (CurlException | ConnectorException $e) {
            throw new OnRepeatException($dto, $e->getMessage(), $e->getCode(), $e);
        }

        return $dto;
    }
}
```

Opět nezapomeneme registraci.

``` YAML 8


# ./config/connector/connector.yaml
service:
    hbpf.connector.hub-spot.create-contact:
        class: Pipes\PhpSdk\Connector\HubSpot\HubSpotCreateContactConnector
        arguments:
            - '@doctrine_mongodb.odm.default_document_manager'
            - '@hbpf.transport.curl_manager'
        calls:
            - ['setApplication', ['@hbpf.application.hub-spot']]
            - ['setLogger', ['@monolog.logger.commons']]
```

Pokud máme toto vše hotové, můžeme finálně upravit proces a otestovat.

## Nastavení služeb a otestování procesu
V editoru procesu v aplikaci PIPES Admin nyní přiřadíme nové scripty jednotlivým uzlům našeho procesu. Pokud jsme vše udělali správně, měli bychom je mít dostupné v nabídce pro jednotlivé akce. Finální proces bude vypadat následovně:

![](/uploads/scr_batch_splitter/4_batch_splitter_new_topology.png "Upravená topologie s Batch Splitterem")

Přepneme se do záložky metrik procesu a spustíme proces.

![](/uploads/scr_batch_splitter/5_batch_splitter_run.png "Spuštění topologie")

Až proces doběhne, měli bychom vidět v metrikách jednotlivých uzlů, že zatím co první connector a batch splitter zpracovali jednu instanci procesu, mapper a HubSpot connector už zpracovávaly po 10 instancích. 

![](/uploads/scr_batch_splitter/6_batch_splitter_metrics.png "Metriky procesu")

Výsledek samozřejmě můžeme ověřit v aplikaci HubSpot.

``` infoBlock
Pokud v Hubspotu kontakt existuje, dojde při dalším spuštění proces k jeho odmítnutí. 
HubSpot vrací status 409, který označuje vytváření duplicitního kontaktu. 
Jestliže budete chtít proces pouštět vícekrát je nutné kontakty v HubSpotu promazat před spuštěním. 
Pokud tak neučiníte uvidíte mezi logy chybu: <strong>Contact "telly.hoeger@billy.biz" already exist</strong>.
```

Gratulujeme, nyní už umíte používat v procesu batch splitter, což je velice užitečný nástroj při procesech, které nesynchronizují data realtime. V následujícím manuálu si ukážeme, [jak nastavit plánované spouštění procesu a jak používat stránkování při získávání dat](/docs/cs/tutorials/scheduled-process).
