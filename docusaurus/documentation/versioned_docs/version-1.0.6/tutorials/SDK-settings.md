# Connect worker with Orchesty

In this chapter, we create a new service and register it as a worker in Orchesty.
This way we can build any number of workers which we can use in our processes.
We can implement connectors, data transformations or filters within them. We can register any application with implemented SDK as a worker in Orchesty.

## What do we need?

- Installed Orchesty on localhost from [Installation](../get-started/installation.md). 

:::note for PHP developers
In the Orchesty-skeleton we have a repository with a service that has the Orchesty SDK in its dependencies. The Orchesty PHP SDK is actually a Symfony bundle. So we can use any project built on Symfony to install the necessary dependencies using composer. For a better understanding, we recommend reading the article on [Orchesty architecture and development](../get-started/architecture).
:::

## Worker registration
In the root folder of our new project we will find folders with ready applications that implement the necessary SDK. As a first step, we'll choose an application in Node.js and teach it to communicate with the orchestration layer. This application will be used to build connectors and transformation scripts that we will use within our processes.

To integrate directly with Orchesty, you can now register the service inside [Orchesty Admin](../admin/admin.md). This is available in the **Workers** tab on the left sidebar or at [http://127.0.0.10/workers](http://127.0.0.10/workers).

![Services](/img/tutorial/sdk-implementation.svg "Services")

Click on **Create** and enter the name of the worker and its URL. We can fill in the URL in the form of **IP address** or **hostname** that we have specified in **docker-compose.yml**.

![Registration SDK](/img/tutorial/add-sdk.svg "Services")

:::info
The Orchesty installation includes prepared skeletons with SDKs for Node.js and PHP. The default skeleton for Node.js is registered in docker-compose at **nodejs-sdk:8080**. For more information about the project structure, see [Architecture](../get-started/architecture.md).
:::
## Settings headers of communication
In the worker registration or modification window, you can set the necessary headers for HTTP communication. These can be used, for example, to insert an authorization token. We will not use the headers for our example.

:::tip
You can register any number of services to orchestrate the entire microservice architecture of a project. The SDK will simplify the work and substitute the API of the orchestrated services.
:::

Congratulations, we have registered the first worker to the orchestration layer. In the next chapter, we will create the first script of the new worker and show how to use it in the new process.
