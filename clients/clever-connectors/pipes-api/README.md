# SETTING UP NEW PROJECT

Basic steps to set up a new project.

### Create New Symfony Project

Download symfony installer, e.g. to `pipes/clients/clever-monitor/bin/symfony`:
```
sudo curl -LsS https://symfony.com/installer -o .../bin/symfony
sudo chmod a+x .../bin/symfony
```

Run symfony installer command to create new project:
```
cd clever-monitor
bin/symfony new cm-api
```

See [http://symfony.com/doc/current/setup.html](http://symfony.com/doc/current/setup.html).

### Docker

Create folder `docker/php-dev/` under `clever-monitor/cm-api/` and put following files in it:
```
Dockerfile
entrypoint.sh
php-local.ini
```

Create folder `docker/nginx/` under `clever-monitor/cm-api/` and put following files in it:
```
nginx.conf
```

### Docker-compose

Create `docker-compose.yml` file and define the services for the project.

### Composer

Add `hanaboso/pipes-framework` and `phpoffice/phpspreadsheet` to composer.json.

### Register Bundles

Add needed bundles to AppKernel.php:
```
$bundles = [
    ...
    new DoctrineMongoDBBundle(),
    new FOSRestBundle(),
    new JMSSerializerBundle(),
    new RabbitMqBundle(),
    new HbPFApiGatewayBundle(),
    new HbPFCommonsBundle(),
    ...
];
```

### Makefile

Make sure you have `Makefile` file with all necessary commands in root folder.

### .env

Create/copy file .env.dist to project's root (`.../clever-monitor/cm-api/`), set `DEV_IP` value and run command:

`make .env`

which will create `.env` file.

### Launch the App!

When everything is ready you can run the app:

```
make docker-up
make composer-install
```