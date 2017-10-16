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