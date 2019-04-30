Multi Probe

This microservice to be used for checking API endpoints availability.
In the Pipes Framework it is used for checking HTTP workers statuses.

While app is running:
1. register worker's status endpoint using `add` route, providing the url and topologyId
2. you may list which workers are registered by `list` route
3. You may check all the statuses of workers related to single topology by calling `status` route 
 

Please see notes in Confluence: https://hanaboso.atlassian.net/wiki/spaces/PIP/pages/85229644/Topology+Probe+-+multi+probe

Routes:
- GET probe:8007/topology/list
- POST probe:8007/topology/add
- GET probe:8007/topology/remove?topologyID=XYZ
- GET probe:8007/topology/status?topologyID=XYZ

How to test:
make go-test

How to build:
make docker-build
make docker-push

 