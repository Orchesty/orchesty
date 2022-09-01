import Image from '/src/components/ThemedImg';
import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Architecture and development

Orchesty is a microservice platform whose individual services run in a virtualized environment of **Docker containers**.
The whole system is designed to be scalable and extensible/expandable by connectors, custom codes or microservices.

Orchesty jako integrační nástroj vytváří 2 základní vrstvy - **integrační vrstvu** a **orchestrační vrstvu**. Každá z nich má svůj význam a dohromady vytváří architekturu, která izoluje zodpovědnost jednotlivých integrovaných služeb od logiky procesů, kterých se účastní.

![Layers](/img/architecture/architecture-layers.svg "Orchesty layers")

## Integrační vrstva

Integrační vrstvu tvoříme pomocí [SDK balíčků](../get-started/SDK.md) a obsahuje námi vytvářené kódy pro transformace dat a konektory pro komunikaci s API integrovaných služeb. Jedná se o **code based** část projektu, kterou lze budovat s komfortem, na který jsme zvyklí při běžných vývojových projektech. Orchesty nelimituje svým vývojovým prostředím. Můžeme používat vlastní IDE i verzování v repozitářích dle naší volby. Naším pomocníkem je framework, obsažený v SDK balíčcích. 

## Orchestrační vrstva

Orchestrační vrstva slouží k modelování a řízení asynchronních procesů z jednotlivých komponent integrační vrstvy. Mezi jednotlivými uzly procesu vytváří message fronty pomocí **RabbitMq**. K frontám dodává high level funkce a byznysovou diagnostiku, takže výrazně urychluje budování procesů a usnadňuje jejich následnou správu. 

## Orchesty Admin

K práci s orchestrační vrstvou slouží uživatelské rozhraní Orchesty Admin, kde najdeme nástroje pro modelování, řízení i diagnostiku procesů.
 
:::note Usefull links
- [Integrations](../get-started/integration.md)
- [Orchestration](../get-started/orchestration.md)
- [Orchesty Admin](../get-started/admin.md)
:::

## Skeleton repository

Základem pro práci s Orchesty je mono-repository [Orchesty-skeleton](https://github.com/Orchesty/orchesty-skeleton). Obsahuje výchozí adresáře pro budování služeb s instalovanými [SDK balíčky](../get-started/SDK.md) pro jednotlivé programovací jazyky. Je jen na nás, které SDK chceme využít. 

V rámci mono-repository můžeme budovat libovolný počet služeb pro [Direct Integration](../get-started/integration.md) jednoduše kopírováním obsahu složky zvoleného SDK do složky na stejné úrovni.

```- title="mono-repository folder structure"
my-app/
├── php-sdk/
│   ├── bin
│   ├── config
│   ├── ...
│   ├── Makefile
│   └── ...
└── nodejs-sdk/
│   ├── bin
│   └── ...
└── my-new-service/
│   ├── bin
│   └── ...
└── docker-compose.yml
```

Novou službu následně přidáme do **docker-compose.yml**.

```yaml title="docker-compose.yml"
services:
   my-new-service:
       image: my-new-service/image:tag
       user: ${DEV_UID}:${DEV_GID}
       working_dir: /var/www
       volumes:
           - ./my-new-service:/var/www:cached
```
:::tip
If you don’t have any experience with **docker compose**, we recommend reading the [original documentation](https://docs.docker.com/compose/).
:::

## Integrace služeb mimo skeleton repository

V Orchesty lze registrovat pro **přímou integraci** jakoukoliv službu s instalovaným [Orchesty SDK](../get-started/SDK.md), spravovanou v libovolném repozitáři. Můžeme takto snadno orchestrovat servisní nebo mikroservisní architekturu napříč naší infrastrukturou.
