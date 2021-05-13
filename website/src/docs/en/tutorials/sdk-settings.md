---
layout: main.hbs
collection: documentation
name: How to set up your service using the SDK for direct integration with PIPES
parent: Tutorials
level: 2
index: 1
lang: en

lunr: true
tags: sdk settings
---

This tutorial shows how to create a custom service that will be integrated with the Orchestration layer PIPES. This way we can build containers with custom services which will be used in our processes. Besides data transformations, those containers can be used for custom sets of applications and connectors.

## What do we need?
- The first necessary condition is to have PIPES installed into your localhost. More about: [Installation & running of PIPES](/docs/en/installation).

Installation of PIPES skeleton provides a prepared repository with the service which has PIPES SDK in its dependencies. PHP SDK PIPES is a Symfony bundle. Therefore you can use any arbitrary project build on Symfony, in which you install required dependencies with composer. See also [PIPES architecture & development](/docs/en/architecture).


## Service registration in PIPES Admin
By registering service in [PIPES Admin](/docs/en/admin), we enable the direct integration with PIPES. PIPES Admin is available on [http://127.0.0.10/ui/](http://127.0.0.10/ui/). Open the tab **Services**:

![Services](/uploads/scr_sdk_settings/1_pipes_admin_sdk_implementation.png "Services")


Choose **Create** and type the service name and its URL. URL can be filled in as **IP address** or **hostname**, which is stated in **docker-compose.yml**.

![SDK registration into PIPES](/uploads/scr_sdk_settings/2_pipes_admin_add_sdk.png "SDK registration into PIPES")

Congratulations, this is how you can register an arbitrary number of services, running in their containers, available for you to build processes and integrations. Let's create our [first process](/docs/en/tutorials/first-process).