# Stream server

Stream server consumes messages from predefined rabbitMQ queue and forwards it to subscribed websockets clients.
Every message must contain json with following fields:

 - event: event string name
 - groups: array of group ids to which message should be send
 - content: whatever message you want to send to clients
 
In order to receive messages, users must connect to Stream server using websockets connection.
When the ws connection is established, every user can subscribe for messages of one or more groups.
To subscribe ws client must send subscribe message to ws server.
Subscription is limited to 5 minutes, so client must send subscribe message periodically,
if he wants to receive ws messages for longer time period.

Subscription is valid only for logged users in the remote system, thus when the user logs-in inside remote system, it
send information about this user and allowed groups to Stream server via http. This information is validated during
subscribe action.

## Configuration ENV parameters:
- *ENV* - *default* (comment)


- STREAM_WS_PORT - 8080 (port the ws server listens to)
- STREAM_WS_ORIGINS - \*:\* (CORS settings)
- STREAM_SUBSCRIBE_TIMEOUT - 300000 (5min)
- STREAM_QUEUE - pipes.stream (the rabbitmq queue the app reads messages from)

 
- RABBITMQ_HOST - rabbitmq
- RABBITMQ_USER - guest
- RABBITMQ_PASS - guest
- RABBITMQ_PORT - 5672
- RABBITMQ_VHOST - /
- RABBITMQ_HEARTBEAT - 60


- STREAM_HTTP_PORT - 3030 (port the http server listens to)
- STREAM_ROUTE_LOGIN - /login 
- STREAM_ROUTE_LOGOUT - /logout

## How to use:

- Start the server.
- Make http request to /login route to register logged-in users and granted groups (provide userId and array of groups)
- From client page send subscribe ws message with defined userId and groups subset of groups sent via http request before
- Send amqp messages to input queue. These will be distributed to connected ws clients with granted access


## Interfaces

#### AMQP message example:
```
{
    "event": "test",
    "content": "some test message content",
    "groups": ["b"]
}
```

#### Login:
####### Request:
Send POST request with following body to **3030:/login** route
```
{
    "userId": "some-user-email-or-id-string",
    "groups": [
        "group-ame",
        "another-group-name",
    ]
}
```

####### Response:
```
{
    "userId": "some-user-email-or-id-string",
    "token": "uuid-token"
}
```

#### Logout:
####### Request:
Send POST request with following body to **3030:/logout** route
```
{
    "token": "uuid-token"
}
```

####### Response:
```
{
    "userId": "some-user-email-or-id-string"
}
```

#### Connect to web sockets:
**Server:** http://hostname:8080/stream
**Subscribe:** socket.emit('subscribe', { token: "uuid-token", groups: ["groupName"] });
**Unsubscribe:** socket.emit('unsubscribe', { token: "uuid-token", groups: ["groupName"] });
