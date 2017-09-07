import {connect} from 'react-redux';

import * as nodeActions from '../../../actions/nodeActions';

import NodeListTable from './NodeListTable';


function mapStateToProps(state, ownProps) {
  const {node} = state;
  const list = node.lists['@topology-' + ownProps.topologyId];
  return {
    list: list,
    state: list && list.state,
    elements: node.elements,
    withTopology: ownProps.withTopology !== undefined ? ownProps.withTopology : false
  }
}

function mapActionsToProps(dispatch, ownProps){
  const needList = forced => dispatch(nodeActions.needNodesForTopology(ownProps.topologyId, forced));
  return {
    needList,
    notLoadedCallback: needList,
    updateNode: (id, data) => dispatch(nodeActions.nodeUpdate(id, data)),
    runNode: id => dispatch(nodeActions.nodeRun(id))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(NodeListTable);