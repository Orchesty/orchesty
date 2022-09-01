import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# Result codes

Result code also known as Result status is controlling header for message within Orchesty framework. By specifying this
code we are telling how to treat given message. If it was processed ok, with an error, should be stopped, exceeded 3rd
party limits, ...

Within nodejs-sdk is a number of prepared method for controlling it, so you don't have to set it manually.

Possible values:

- Ok
- DoNotContinue
- StopAndFail
- Repeat
- ForwardToQueue
- LimitExceeded
- CursorWithFollowers
- CursorOnly

### Ok

Standard value that everything is as it should be and message continues.

### Do not continue

Ok status for successful process but does not continue in topology process
so no following nodes are called.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
dto.setStopProcess(ResultCode.DO_NOT_CONTINUE);
```
</TabItem>
</Tabs>

### Stop and fail

Error status, where messages falls into thrash and whole topology process is marked as failed.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
dto.setStopProcess(ResultCode.STOP_AND_FAILED);
```
</TabItem>
</Tabs>

### Repeat

Message is send to repeater and after given delay returned to the same node to retry process. Usable for example when
3rd party service is not responding.

Common way is to throw an exception:

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
const delay = 60; // seconds
const maxHops = 10; // stop retrying and send message to thrash after x hops
throw new OnRepeatException(delay, maxHops, 'reason of repeating');
```
</TabItem>
</Tabs>


### Forward to queue

When node has more than one follower, you can specify which of them should receive and continue to process this message.
Message is by default sent to all followers and thus duplicated for each one of them.

To name desired followers you have to call setForceFollowers method providing it with names of nodes.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
dto.setForceFollowers('some-connector', 'another-connector');
```
</TabItem>
</Tabs>


:::tip
Avoid misspelling by using exported node names
:::

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
export const NAME = 'some-connector';

export class SomeConnector extends AConnector {

  public getName(): string {
    return NAME;
  }

}
```
</TabItem>
</Tabs>

### Limit exceeded

For case where message exceeded 3rd party service API limits. Message is returned to limiter
to wait for another round.

!! TODO tohle teď nelze přímo nastavit -> musela by se nastavit přímo hlavička.

### Cursor with followers

Settings message for cursoring (repeated processing) while also sending result data to it's followers.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
dto.setBatchCursor(cursorKey);
```
</TabItem>
</Tabs>

### Cursor only

Settings message for cursoring (repeated processing) **without** sending result data to it's followers.

<Tabs>
<TabItem value="typescript" label="Typescript">

```typescript
dto.setBatchCursor(cursorKey, true);
```
</TabItem>
</Tabs>
