import * as types from 'rootApp/actionTypes';

function receive(topologyGroup){
  return {
    type: types.TOPOLOGY_GROUP_RECEIVE,
    topologyGroup
  }
}

function receiveItems(items){
  return {
    type: types.TOPOLOGY_GROUP_RECEIVE_ITEMS,
    items
  }
}

function remove(id){
  return {
    type: types.TOPOLOGY_GROUP_REMOVE,
    id
  }
}

function createTopologyGroup(topologyElements, topologyGroupId){
  const topologies = Object.keys(topologyElements).filter(id => topologyElements[id].name == topologyGroupId);
  if (topologies.length > 0) {
    return {
      id: topologyGroupId,
      last: topologies.reduce((a, b) => topologyElements[a].version > topologyElements[b].version ? a : b),
      items: topologies
    };
  } else {
    return null;
  }
}

export function recalculateTopologyGroup(topologyGroupId, oldTopology){
  return (dispatch, getState) => {
    if (oldTopology && oldTopology.name != topologyGroupId){
      dispatch(recalculateTopologyGroup(oldTopology.name));
    }
    const topologyGroup = createTopologyGroup(getState().topology.elements, topologyGroupId);
    dispatch(topologyGroup ? receive(topologyGroup) : remove(topologyGroupId));
  }
}

export function recalculateAllTopologyGroups() {
  return (dispatch, getState) => {
    const topologyElements = getState().topology.elements;
    const uniqueTopologyGroupIds = [];
    Object.keys(topologyElements).forEach(id => {
      const topology = topologyElements[id];
      if (uniqueTopologyGroupIds.indexOf(topology.name) == -1){
        uniqueTopologyGroupIds.push(topology.name);
      }
    });
    dispatch(receiveItems(uniqueTopologyGroupIds.map(id => createTopologyGroup(topologyElements, id))));
  }
}

