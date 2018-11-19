import React from 'react';
import {connect} from 'react-redux';

import * as nodeActions from 'actions/nodeActions';
import * as topologyActions from 'actions/topologyActions';
import * as applicationActions from 'actions/applicationActions';

import NodeListTable from './NodeListTable';
import {stateType} from 'rootApp/types';
import stateMerge from 'rootApp/utils/stateMerge';

class TopologyNodeListTable extends React.Component{
  constructor(props){
    super(props);
    this.state = {topologyState: stateType.NOT_LOADED};
    this.needData = this.needData.bind(this);
  }

  _needTopology(){
    const {topologyId, topologyElements, needTopology} = this.props;
    const topology = topologyElements[topologyId];
    if (topology === undefined){
      if (this.state.topologyState !== stateType.LOADING) {
        this.setState({topologyState: stateType.LOADING});
        needTopology().then(response => {
          this.setState({topologyState: stateType.SUCCESS});
        });
      }
    } else if (this.state.topologyState !== stateType.SUCCESS) {
      this.setState({topologyState: stateType.SUCCESS});
    }
  }

  needData(){
    this._needTopology();
    this.props.needList(false)
  }

  render(){
    const {listState, needTopology, ...passProps} = this.props;
    const state = stateMerge([listState, this.state.topologyState]);
    return <NodeListTable notLoadedCallback={this.needData} state={state} {...passProps} />
  }
}

function mapStateToProps(state, ownProps) {
  const {node, topology} = state;
  const list = node.lists['@topology-' + ownProps.topologyId];
  return {
    list: list,
    listState: list && list.state,
    elements: node.elements,
    topologyElements: topology.elements,
    tests: node.tests,
    withTopology: ownProps.withTopology !== undefined ? ownProps.withTopology : false,
    withNodeTest: ownProps.withNodeTest !== undefined ? ownProps.withNodeTest : true
  }
}

function mapActionsToProps(dispatch, ownProps){
  const needList = forced => dispatch(nodeActions.needNodesForTopology(ownProps.topologyId, forced));
  return {
    needList,
    needTopology: forced => dispatch(topologyActions.needTopology(ownProps.topologyId, forced)),
    updateNode: (id, data) => dispatch(nodeActions.nodeUpdate(id, data)),
    runNode: id => dispatch(applicationActions.openModal('node_run', {nodeId: id})),
  }
}

export default connect(mapStateToProps, mapActionsToProps)(TopologyNodeListTable);