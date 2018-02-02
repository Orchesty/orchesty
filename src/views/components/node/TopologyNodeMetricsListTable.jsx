import React from 'react';
import {connect} from 'react-redux';

import * as nodeActions from 'actions/nodeActions';
import * as topologyActions from 'actions/topologyActions';
import * as applicationActions from 'actions/applicationActions';

import {stateType} from 'rootApp/types';
import stateMerge from 'rootApp/utils/stateMerge';
import * as metricsActions from 'rootApp/actions/metricsActions';
import NodeMetricsListTable from './NodeMetricsListTable';

class TopologyNodeMetricsListTable extends React.Component{
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
    } else if (this.state.topologyState != stateType.SUCCESS) {
      this.setState({topologyState: stateType.SUCCESS});
    }
  }

  needData(){
    this._needTopology();
    this.props.needList(false);
    this.props.needMetricsList(false);
  }

  render(){
    const {listState, metricsState, needTopology, ...passProps} = this.props;
    const state = stateMerge([listState, this.state.topologyState, metricsState]);
    return <NodeMetricsListTable notLoadedCallback={this.needData} state={state} {...passProps} />
  }
}

function mapStateToProps(state, ownProps) {
  const {node, topology, metrics} = state;
  const list = node.lists['@topology-' + ownProps.topologyId];
  const metricsList = metrics.topologies[ownProps.topologyId];
  return {
    list: list,
    listState: list && list.state,
    elements: node.elements,
    metricsList: metricsList,
    metricsState: metricsList && metricsList.state,
    metricsElements: metrics.elements,
    topologyElements: topology.elements,
    withTopology: ownProps.withTopology !== undefined ? ownProps.withTopology : false,
  }
}

function mapActionsToProps(dispatch, ownProps){
  const needList = forced => dispatch(nodeActions.needNodesForTopology(ownProps.topologyId, forced));
  return {
    needList,
    needTopology: forced => dispatch(topologyActions.needTopology(ownProps.topologyId, forced)),
    needMetricsList: forced => dispatch(metricsActions.needTopologyMetrics(ownProps.topologyId, ownProps.metricsRange, forced))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(TopologyNodeMetricsListTable);