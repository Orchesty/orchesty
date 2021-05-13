---
layout: main.hbs
collection: documentation
name: Jak vytvořit konektor pro volání REST API
parent: Tutoriály
level: 2
index: 10

lunr: true
tags: basic connector konektor
lang: cs
---


V tomto článku se naučíme vytvořit konektor, kterým získáme data ze služby s rozhraním REST API. Konektor je v podstatě základním prvkem integračního procesu a jeho úkolem je odeslání požadavku a vyhodnocení odpovědi. V případě úspěšného volání konektor předává získanou odpověď do procesní topologie, kde s nimi můžeme dál pracovat. Úlohou konektoru je ale i vyhodnocení chybových odpovědí a nastavení chování po získání chybového kódu. PIPES nabízí několik možných scénářů, jak chybový stav volání ošetřit:

- Opakované volání pomocí Repeateru, kdy můžeme nastavit počet pokusů a interval mezi nimi.
- Ukončení instance procesu, tedy vyhodnocení procesu jako neúspěšného. Tato možnost se nabízí i jako scénář po posledním neúspěšném opakovaném volání. 
- Ignorování stavu, případně ošetření stavu v datech instance procesu.
- Nastavení Limiteru, což je možnost, která se využívá při překročení limitů volání vzdálené služby. Limiteru lze nastavit maximální počet volání v určitém časovém úseku. Jeho využití popisuje samostatná kapitola.

## Co budeme potřebovat?
- Nainstalované PIPES na svém localhostu pro vytvoření nového konektoru. Instalaci můžete provést pomocí návodu [Instalace a spuštění PIPES](/docs/cs/installation).
- Připravenou službu s implementovaným balíčkem SDK, registrovanou v PIPES pro přímou integraci. Pokud službu ještě nemáte, podívejte se na kapitolu [Jak použít vlastní službu s využitím SDK pro přímou integraci s PIPES](/docs/cs/tutorials/sdk-settings/).

## Vytvoření konektoru
Vytvoříme konektor, který využije CURL Manager service pro získání dat z REST API testovací služby. Nejprve vytvoříme třídu konektoru, která rozšiřuje ConnectorAbstract.

``` PHP 1

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;

final class GetUsersConnector extends ConnectorAbstract
{

    use ProcessEventNotSupportedTrait;

    private CurlManager $sender;

    public function __construct(CurlManager $sender)
    {
        $this->sender = $sender;
    }
}
```

Konektoru je potřeba definovat unikátní identifikátor service. K tomu slouží metoda `getId`.

``` PHP 2

public function getId(): string 
{
    return 'get-users';
}
```

Dále vytvoříme metodu, která nastaví volání CURL Manager service. Pro náš první konektor využijeme službu JSONPlaceholder. Můžeme zvolit třeba data s výpisem uživatelů [https://jsonplaceholder.typicode.com/users](https://jsonplaceholder.typicode.com/users).

``` PHP 3

public function processAction(ProcessDto $dto): ProcessDto
{
    $request  = new RequestDto(
        CurlManager::METHOD_GET,
        new Uri('https://jsonplaceholder.typicode.com/users')
    );
    $response = $this->sender->send($request);
    $dto->setData($response->getBody());

    return $dto;
}
```


Následně doplníme vyhodnocení response. Vyhodnotíme všechny možnosti, které mohou při volání služby nastat. V případě úspěšného volání předáme získaná data do objektu ProcessDto.
Pro případ jiných chybových návratových kódů, nebo pokud selžou všechny pokusy Repeateru, vyhodnotíme proces jako ukončený s chybou.

``` PHP 4

// If status code from response is not 200 or 201 process will be stopped as failed
$this->evaluateStatusCode(
    $response->getStatusCode(),
    $dto,
    sprintf('Status code is not valid %s!', $response->getStatusCode())
);
```

V případě nedostupné služby nastavíme Repeater. V tomto případě říkáme Repeatru, aby volání opakoval třikrát v intervalu 1 minuty:

``` PHP 5

$repeat = new OnRepeatException($dto, $e->getMessage());
$repeat
    ->setInterval(60_000)
    ->setMaxHops(3);

throw $repeat;
```

Celý kód konektoru pak vypadá následovně.

``` PHP 6

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Connector\ConnectorAbstract;
use Hanaboso\PipesPhpSdk\Connector\Traits\ProcessEventNotSupportedTrait;
use Hanaboso\Utils\Exception\PipesFrameworkException;

final class GetUsersConnector extends ConnectorAbstract
{

    use ProcessEventNotSupportedTrait;

    private CurlManager $sender;

    public function __construct(CurlManager $sender)
    {
        $this->sender = $sender;
    }

    public function getId(): string
    {
        return 'get-users';
    }

    public function processAction(ProcessDto $dto): ProcessDto
    {
        try {
            $request = new RequestDto(
                CurlManager::METHOD_GET,
                new Uri('https://jsonplaceholder.typicode.com/users')
            );
            $response = $this->sender->send($request);

            // If status code from response is 500 it will throw an exception to start the Repeater
            if ($response->getStatusCode() === 500) {
                throw new CurlException('Service is a unreachable!');
            }

            // If status code from response is not 200 or 201 process will be stopped as failed
            $this->evaluateStatusCode(
                $response->getStatusCode(),
                $dto,
                sprintf('Status code is not valid %s!', $response->getStatusCode())
            );

            $dto->setData($response->getBody());
        } catch (CurlException | PipesFrameworkException $e) {
            $repeat = new OnRepeatException($dto, $e->getMessage());
            $repeat
                ->setInterval(60_000)
                ->setMaxHops(3);
            throw $repeat;
        }
        return $dto;
    }
}
```

Zbývá zaregistrovat třídu konektoru jako službu, kterou pojmenujeme ``get-users``. Tím máme konektor připravený k použití.

``` YAML 7

# ./config/connector/connector.yaml
service:
    hbpf.connector.get-users:
        class: Pipes\PhpSdk\Connector\Users\GetUsersConnector
        arguments:
            - '@hbpf.transport.curl_manager'
```

## Použití konektoru v integračním procesu
Přihlásíme se do uživatelského rozhraní a vytvoříme nový proces. V menu klikneme na **File -> New topology**.

V detailu topologie se přepneme do záložky editoru. Nyní vytvoříme jednoduchý proces a ukážeme si, jak snadno nový konektor v procesu použijeme. Přetáhneme **Start event** z toolbaru na canvas.
V toolbaru vybereme prvek **Connector**, vložíme ho na canvas a propojíme se Start eventem.

![](/uploads/scr_basic_connector/1_newtopo_bpmn.png "Basic Connector")

Nyní nastavíme pro novou akci script, který bude vykonávat. V našem případě se jedná o testovací konektor, který jsme pojmenovali ``get-users``. Klikneme tedy na prvek akce na canvasu a v pravém sidebaru klikneme na rozbalovací nabídku **Name**. Pokud jsme správně provedli všechny předchozí kroky, měli bychom náš konektor vidět v rozbalovací nabídce. Pokud se konektor nezobrazil, zkontrolujeme že máme správně zaregistrovanou službu s SDK balíčkem a v ní náležitě zaregistrovanou službu s novým konektorem. Vybereme tedy položku ``get-users``. 

![](/uploads/scr_basic_connector/2_newtopo_bpmn_select.png "Select script")

Tím máme vytvořený první proces, který získá data z testovací služby.

## Otestování procesu

Pro jednoduchý náhled na získaná data a otestování konektoru připojíme na konec topologie user debug task, který nám umožní zobrazit data procesu v uživatelském rozhraní. 

![](/uploads/scr_basic_connector/3_newtopo_bpmn_debug.png "Debug task")

Připravený proces nyní uložíme a publikujeme. Uděláme to pomocí rozbalovací nabídky v pravém horním rohu. Tím PIPES vygenerují Docker kontejner s řídící službou procesu a proces je připraven k otestování.

![](/uploads/scr_basic_connector/4_newtopo_publish.png "Pulikování procesu")

Tlačítkem v horní liště procesu se přepneme do zobrazení metrik. Zde vidíme nejprve blok zobrazující metriky procesu a následně bloky zastupující jednotlivé uzly procesu. 

![](/uploads/scr_basic_connector/5_newtopo_start.png "Metriky procesu")

První blok procesních uzlů zastupuje Start event. Liší se od ostatních mimo jiné tlačítkem pro spuštění instance procesu. Kliknutím na toto tlačítko získáme možnost vložit data, pokud proces na vstupu nějaké očekává. Protože náš proces žádná vstupní data neočekává, můžeme rovnou spustit testovací instanci.

![](/uploads/scr_basic_connector/6_newtopo_run.png "Spuštění procesu")

Nyní přejdeme v horní liště do záložky **User Tasks**, kde ve zobrazené tabulce uvidíme záznam našeho debugovacího uzlu. Po rozkliknutí detailu záznamu uvidíme data, která nám v rámci této instance procesu přišla.

![](/uploads/scr_basic_connector/7_human_tasks_table.png "Debug pomocí User Tasku")

## Automatizovaný test

Na závěr si ještě ukážeme, jakým způsobem můžeme napsat pro vytvořený konektor automatizovaný test. Rozhodně doporučujeme automatizované testy psát, protože testování by mělo být základem každé programátorské práce.

``` PHP 8

use Exception;
use Hanaboso\Utils\String\Json;
use Pipes\PhpSdk\Connector\Users\GetUsersConnector;
use Pipes\PhpSdk\Tests\DatabaseTestCaseAbstract;
use Pipes\PhpSdk\Tests\DataProvider;

final class GetUsersConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Pipes\PhpSdk\Connector\Users\GetUsersConnector::processAction
     * @group  live
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $curl      = self::$container->get('hbpf.transport.curl_manager');
        $connector = new GetUsersConnector($curl);

        $resp = $connector->processAction(DataProvider::getProcessDto());
        self::assertNotEmpty($resp->getData());
        $data = Json::decode($resp->getData());
        self::assertCount(10, $data);
    }
}
```

Doufáme, že vám tento návod pomohl. Naučili jsme se vytvořit jednoduchý konektor, který využijeme, jestliže že se nemusíme napojovat na víc endpointů dané služby a pokud si vystačíme s Basic autorizací. V našem dalším návodu si ukážeme, jak vytvořit vlastní aplikaci, kterou lze následně využít pro psaní více konektorů jedné služby. [Klikněte zde, pokud se chcete naučit vytvářet vlastní aplikace.](/docs/cs/tutorials/basic-application)