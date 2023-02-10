import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# SDK settings

V tomto návodu se naučíme vytvořit vlastní službu, kterou integrujeme s orchestrační vrstvou PIPES. Tímto způsobem můžeme budovat kontejnery s vlastními službami, které chceme využívat ve svých procesech. Kromě datových transformací lze tyto kontejnery se službami využívat i pro vlastní sady aplikací a konektorů.

## Co budeme potřebovat?
- Nainstalované PIPES na svém localhostu pro vytvoření nového konektoru. Instalaci můžete provést pomocí návodu [Instalace a spuštění PIPES](../get-started/installation).

Instalací PIPES skeletonu máme připravený repozitář se službou, která má ve svých závislostech PIPES SDK. PHP SDK PIPES je ve skutečnosti Symfony bundle. Můžeme tedy využít i libovolný projekt, budovaný na Symfony, ve kterém pomocí composeru nainstalujeme potřebné závislosti. Pro lepší pochopení doporučujeme prostudovat článek o [architektuře a developmentu PIPES](../get-started/architecture).


## Registrace služby v PIPES Admin
Pro přímou integraci s PIPES nyní stačí registrovat službu uvnitř [PIPES Admin](../admin/admin.md). Ten je dostupný na [http://127.0.0.10](http://127.0.0.10). Otevřeme záložku **Implementations**:


Klikneme na **Create** a zadáme název služby a její URL. URL můžeme vyplnit ve tvaru **IP adresy** nebo **hostname**, které máme uvedeno v **docker-compose.yml**.


Gratulujeme, tímto způsobem můžeme registrovat libovolné množství služeb, které poběží ve svých kontejnerech a budeme je mít k dispozici pro budování procesů a integrací. Pojďme si tedy vytvořit náš [první proces](../tutorials/first-process).
