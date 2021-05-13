---
layout: main.hbs
collection: documentation
name: Jak použít vlastní službu s využitím SDK pro přímou integraci s PIPES
parent: Tutoriály
level: 2
index: 1

lunr: true
tags: nastavení sdk
lang: cs
---


V tomto návodu se naučíme vytvořit vlastní službu, kterou integrujeme s orchestrační vrstvou PIPES. Tímto způsobem můžeme budovat kontejnery s vlastními službami, které chceme využívat ve svých procesech. Kromě datových transformací lze tyto kontejnery se službami využívat i pro vlastní sady aplikací a konektorů.

## Co budeme potřebovat?
- Nainstalované PIPES na svém localhostu pro vytvoření nového konektoru. Instalaci můžete provést pomocí návodu [Instalace a spuštění PIPES](/docs/cs/installation).

Instalací PIPES skeletonu máme připravený repozitář se službou, která má ve svých závislostech PIPES SDK. PHP SDK PIPES je ve skutečnosti Symfony bundle. Můžeme tedy využít i libovolný projekt, budovaný na Symfony, ve kterém pomocí composeru nainstalujeme potřebné závislosti. Pro lepší pochopení doporučujeme prostudovat článek o [architektuře a developmentu PIPES](/docs/cs/architecture).


## Registrace služby v PIPES Admin
Pro přímou integraci s PIPES nyní stačí registrovat službu uvnitř [PIPES Admin](/docs/cs/admin). Ten je dostupný na [http://127.0.0.10/ui/](http://127.0.0.10/ui/). Otevřeme záložku **Implementations**:

![Implementations](/uploads/scr_sdk_settings/1_pipes_admin_sdk_implementation.png "Implementations")

Klikneme na **Create** a zadáme název služby a její URL. URL můžeme vyplnit ve tvaru **IP adresy** nebo **hostname**, které máme uvedeno v **docker-compose.yml**.

![Registrace SDK do PIPES](/uploads/scr_sdk_settings/2_pipes_admin_add_sdk.png "Registrace SDK do PIPES")

Gratulujeme, tímto způsobem můžeme registrovat libovolné množství služeb, které poběží ve svých kontejnerech a budeme je mít k dispozici pro budování procesů a integrací. Pojďme si tedy vytvořit náš [první proces](/docs/cs/tutorials/first-process).