import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Results evaluation
Orchesty offers several options for evaluating remote service communication responses. In addition to a successful call, there are situations that indicate temporary problems where we may want to retry the call. We may also get a response with a code telling us that retrying is pointless. Then it depends on the specific case how the process should proceed.

## Repeater
If the called service does not answer us or answers with a code that indicates temporary unavailability, we use a repeater. This will ensure repeated calls according to the defined number and frequency. If all retries are unsuccessful, the process is terminated with an error and the message is redirected to [trash](../documentation/trash).

### Repeater settings in the connector code
In the connector, we set the repeater calls as an exception:

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
const delay = 60; // seconds
const maxHops = 10; // stop retrying and send message to thrash after x hops
throw new OnRepeatException(delay, maxHops, 'reason of repeating');
```
</TabItem>
</Tabs>

A simplified way is to set the repetition directly in the `send` method:

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
const delay = 60;
const maxHops = 10;
const response = await this.getSender().send(request, [201, 409], delay, maxHops);
```
</TabItem>
</Tabs>

This told the sender to repeat everything except codes 201 and 409. The default for the repeater setting is 10 repeats of 60 seconds each. So in our example, we didn't need to specify the last 2 parameters.

We can specify the range of codes for repeating the call as follows:

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
const delay = 60;
const maxHops = 10;
const response = await this.getSender().send(request, [createRepeatRange(300, 500)], delay, maxHops);
```
</TabItem>
</Tabs>

So the repeater will repeat everything with codes between 300 and 500.

### Settings in admin
Repeater can also be configured in the topology editor. This is less common, but can be useful for example when debugging the topology, when we want to temporarily modify the repeater.

![Repeater settings in editor](/img/documentation/repeater-settings-bar.svg)

## Termination of processing by error

If there is no point in repeating the call and we want to terminate the message processing, we have two options. Terminate only the message run and without affecting the evaluation of the process, or mark the process as failed.

### End message processing

In this case, sending the message to other nodes is stopped, but the evaluation of the process is not affected. The message is moved to the trash.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
dto.setStopProcess(ResultCode.DO_NOT_CONTINUE);
```
</TabItem>
</Tabs>

### Termination of message and process with failure

Message processing is stopped and the process is marked as failed. The message is moved to the trash.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
dto.setStopProcess(ResultCode.STOP_AND_FAILED);
```
</TabItem>
</Tabs>

