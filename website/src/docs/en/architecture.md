---
layout: main.hbs
collection: documentation
name: Architecture and development
parent: Getting started
level: 2
index: 2
lang: en
 
lunr: true
tags: architecture
---

PIPES, a microservice platform whose individual services run in a virtualized environment of Docker containers. The whole system is designed to be scalable and extensible/expandable by custom connectors and data transformation services. More information about extensions at [Custom PIPES Extension](/docs/en/extension). 
In this article we describe extensions only in terms of service architecture and development.

## How to write your own code of integration processes

All extensions, mostly data transformations and custom connectors, can be written as separate services, which only have to implement one of the [PIPES SDK packages](/docs/en/sdk). Communication between PIPES orchestration layer and connected service is provided by registering the service into [PIPES Admin](/docs/en/admin). This type of connection to the orchestration layer is called [Direct integration](/docs/en/integration).

![SDK Extensions](/uploads/src_architecture/sdk_extentions.png)

If the application uses Direct integration, the code for each process action can be written directly inside the application itself.  The process actions will then be automatically displayed in the list of editor processes, where they are available to be assigned into process topology. For more information on this topic, read the article [PIPES Custom Extension](/docs/en/extension).

``` infoBlock
Direct integration can be used both for building integration layers and extensible elements of PIPES and in case we integrate services within service or microservice infrastructure where we are allowed to implement PIPES SDK. By using the direct integration, we eliminate the need to build REST API in individual services. The advantage is lower network traffic compared to the implementation of REST API
```

## Project management and versioning
 
The PIPES architecture allows managing the integration layer in a common development environment. It, therefore, doesn’t force developers to use different tools than they usually work with. Writing of the code happens in an arbitrary IDE tool. We use GIT repositories for versioning.

We can either use a **mono-repository** (one repository for all built services) while building application infrastructure, or we can place every service in an individual single repository.

### Mono-repository
The basis for both types of project management is a cloned [Pipes-skeleton](https://github.com/hanaboso/pipes-skeleton) repository. We can add individual services into new directories on to the level of the ``php-sdk`` directory. If we create PHP service for [Direct integration](/docs/en/integration), we copy the content of the default ``php-sdk`` directory into our new directory as well.

``` PROJECT 2
my-app/
├── pipes-sdk/
│   ├── bin
│   ├── config
│   ├── ....
│   ├── Makefile
│   └── ...
└── my-new-service/
│   ├── bin
│   ├── ....
└── docker-compose.yml
```

Adding new service into the docker-compose.yml like this: 

``` docker-compose.yml 1
 
services:
   my-new-service:
       image: my-new-service/image:tag
       user: ${DEV_UID}:${DEV_GID}
       working_dir: /var/www
       volumes:
           - ./my-new-service:/var/www:cached
```

If you don’t have any experience with **docker compose**, we recommend reading the [original documentation](https://docs.docker.com/compose/).

``` infoBlock
<h4>Advantages:</h4>
Possibility to run the whole infrastructure from one central point (docker-compose.yml placed in the root directory of the repository)

<h4>Disadvantages:</h4>
Higher HW standards (CPU + RAM) during local development
```
<br/>
 
### Individual repositories

The mono-repository principle is not always the best option. We can create services for [Direct integration](/docs/en/integration) in individual repositories and work with them like with any other application. We can once again use the [Pipes-skeleton](https://github.com/hanaboso/pipes-skeleton) as a basis for our project. Alternatively, we can use only the content of the directory ``php-sdk`` where is the whole basis of the PHP project.

``` PROJECT 3
 
my-app/
├── pipes-sdk/
│   ├── bin
│   ├── config
│   ├── ....
│   ├── Makefile
│   └── ...
└── docker-compose.yml
 
my-app-2/
├── c-sharp-sdk/
│   ├── bin
|   └── ...
└── docker-compose.yml
```

``` infoBlock
<h4>Advantages:</h4>
Lower HW standards (CPU+RAM) during local development

<h4>Disadvantages:</h4>
Inability to run the infrastructure from one central point
``` 
<br/>

### Symfony
If we have our **Symfony** project, we can just add the following dependencies in the composer:

``` composer.json 4
 
"hanaboso/app-store": "^1.4",
"hanaboso/pipes-php-sdk": "^1.3",
```

In this case, it is necessary to register our services in the config files.
You can look up the configuration in the [pipes-skeleton/php-sdk/config](https://github.com/hanaboso/pipes-skeleton/tree/master/php-sdk/config).

