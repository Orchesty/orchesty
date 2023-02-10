import Image from '/src/components/ThemedImg';

# First process

As a first step, we'll demonstrate basic orchestration layer work and set up a first simple process.

### Prerequisites

- [Installed and running Orchesty](../get-started/installation)

## Creating a process topology
The definition of a process is called a topology. To create a new topology, use the **plus** button in the left Admin bar. Topologies can be organized in folders and in addition to creating a new topology, we also have the option to import a ready-made topology. All of this will come in handy in the future, but for now, we'll just create a topology in the project root.

<Image path="/img/firstProcess/plus-menu.svg" lightOnly />

Select "New topology" from the action menu and name the topology.

<Image path="/img/firstProcess/new-topology.svg" lightOnly />

Now the detail of the topology has opened up. We will describe its environment continuously. But first, let's open the editor and set up our first process. We open the editor with the pencil icon button in the action menu in the upper right corner of the screen.

<Image path="/img/firstProcess/action-menu.svg" lightOnly />

In the left part of the editor we can see the toolbar. The circular elements in the toolbar are events. We use these as the default elements of topologies. You can find here 3 types of events - **timer**, **webhook** and **start**. We'll choose **start** and drag it onto the canvas.

<Image path="/img/firstProcess/editor-start.svg" lightOnly />

:::info
The **Start event** creates an access point of the given topology. It provides a URL for sending data to be processed by the process. The URL is generated when the topology is saved. Then, the start event URL can be found in the right sidebar of the editor when the element is selected.
:::
The next section of the tool editor is **actions**. For our topology, we'll select **user task** and drag it to the canvas. Click on **start event** to display its tools. Then use the arrows to connect the two elements of the topology.

<Image path="/img/firstProcess/first-process-topology.svg" lightOnly />

Thus, we built the first sample topology using the **user task** action.

:::info
The **User task** action is useful for manually editing data using the user interface, but it can also be used very easily for stepping data when building topologies. Below we will show how.
:::

## Publishing a topology

Now save the prepared topology using the **Save** button in the action menu and close the editor using the **Back** button. This saves the changes. The topology is still in the **Draft** state. To run the prepared topology, we still need to publish it using the **Publish** button.

<Image path="/img/firstProcess/action-menu.svg" lightOnly />

:::info
By publishing the topology, Orchesty creates a container with a control service and queues between process nodes.
:::

## Enable/disable process

The newly published process is in the inactive state. That is, it does not receive any start event signals. As soon as we switch the process to the enable state, it starts receiving requests for the start event URL.

<Image path="/img/firstProcess/enable.svg" lightOnly />

:::info
**Disable** of a topology closes its entry points. The topology receives no signals and timers do not start scheduled processes. All running process instances continue processing.
:::

## Manually starting a process

If we have prepared a topology, we can also start the process manually by using the **Run** button in the action menu. In the window that opens, we can select the desired start event if the topology has more than one. In the body of the message we will insert the required data in JSON format.

<Image path="/img/firstProcess/run.svg" lightOnly />

Now start the process and move to the **User tasks** tab in the topology details. In this tab we can see all the messages of all the user tasks of the topology. When we click on our message, we can see its headers and data in the detail. The data can be edited before sending.


So if you see your first message in the user tasks overview, you have successfully started your first process. Use the **Approve** button to complete the process.

In the next chapter, we will show how to register a worker to the orchestration layer. In the worker, we can create our scripts and connectors using the SDK.


