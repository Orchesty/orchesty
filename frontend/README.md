# Frontend
​
## Popis služby
Grafické uživatelské rozhraní Pipes Frameworku.

Topologie: Vytváření, úprava, mazání, publikování, spouštění, testování a klonování  
Notifikace: Nastavení odesílání jednotlivých typů notifikací pomocí emailu, webhooku a RabbitMQ  
Události, které vyžadují akci uživatele (human tasky): Potvrzení, zamítnutí a úprava dat  
CRONy: Přehled všech cronových topologií  
Obchod s aplikacemi: Instalace, nastavení, autorizace a odinstalace  
SDK implementace: Vytváření, úprava a mazání SDK implementací, které mohou být využity v topologiích  
​
## Spuštění služby - development
- `Node.js 12` &amp; `NPM 6` - Vyžadované pro spuštění UI
- `npm start` - Spustí UI na adrese http://0.0.0.0:8081/ui
- `docker-compose exec backend bin/console u:c` - Vytvoří uživatele

## Konfigurační volby
- Nastavení backendu v `src/config/dev/servers.js`
```
export default {
  apiGateway: {
    initDefault: 'default',
    servers: {
      default: {
        caption: 'Pipes Framework Default Backend',
        url: 'http://127.0.0.1/api',
        url_starting_point: 'http://127.0.0.1/starting-point',
      },
    },
  },
};
```
​
## Použité technologie
- JavaScript
    - React
    - Redux
​
## Závislosti
- Backend
