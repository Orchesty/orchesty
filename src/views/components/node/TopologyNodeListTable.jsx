import React from 'react';
import {connect} from 'react-redux';

import processes from 'enums/processes';
import * as nodeActions from 'actions/nodeActions';
import * as topologyActions from 'actions/topologyActions';

import NodeListTable from './NodeListTable';

class TopologyNodeListTable extends React.Component{

  componentWillMount(){
    this._sendActions();
  }

  componentWillUnmount() {
    this.props.setActions(null);
  }

  _sendActions(){
    const {setActions, testTopology, topologyId} = this.props;
    const pageActions = [];
    if (testTopology) {
      pageActions.push({
        caption: 'Test topology',
        action: testTopology,
        processId: processes.topologyTest(topologyId)
      });
    }
    setActions(pageActions);
  }

  render(){
    return <NodeListTable {...this.props} />
  }
}

function mapStateToProps(state, ownProps) {
  const {node} = state;
  const list = node.lists['@topology-' + ownProps.topologyId];
  return {
    list: list,
    state: list && list.state,
    elements: node.elements,
    tests: node.tests,
    withTopology: ownProps.withTopology !== undefined ? ownProps.withTopology : false,
    withNodeTest: ownProps.withNodeTest !== undefined ? ownProps.withNodeTest : true
  }
}

function mapActionsToProps(dispatch, ownProps){
  const needList = forced => dispatch(nodeActions.needNodesForTopology(ownProps.topologyId, forced));
  return {
    needList,
    notLoadedCallback: needList,
    updateNode: (id, data) => dispatch(nodeActions.nodeUpdate(id, data)),
    runNode: id => dispatch(nodeActions.nodeRun(id)),
    testTopology: () => dispatch(topologyActions.testTopology(ownProps.topologyId))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(TopologyNodeListTable);