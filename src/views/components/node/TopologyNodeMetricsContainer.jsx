import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import * as nodeActions from 'actions/nodeActions';
import * as metricsActions from 'actions/metricsActions';
import stateMerge from 'utils/stateMerge';

import StateComponent from 'wrappers/StateComponent';
import NodeMetrics from 'components/metrics/NodeMetrics';
import * as topologyActions from 'rootApp/actions/topologyActions';
import {needTopology} from 'rootApp/actions/topologyActions';
import {stateType} from 'rootApp/types';


class TopologyNodeMetricsContainer extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {nodeList, componentKey} = this.props;
    const nodeItems = nodeList.items.map(nodeId => <NodeMetrics key={nodeId} nodeId={nodeId} componentKey={`${componentKey}.${nodeId}`} />);
    return (
      <div>
        {nodeItems}
      </div>
    );
  }
}

TopologyNodeMetricsContainer.propTypes = {
  componentKey: PropTypes.string.isRequired,
  nodeList: PropTypes.object.isRequired
};

function mapStateToProps(state, ownProps){
  const {node, metrics, topology} = state;
  const nodeList = node.lists['@topology-' + ownProps.topologyId];
  const metricsList = metrics.topologies[ownProps.topologyId];
  const topologyElement = topology.elements[ownProps.topologyId];
  return {
    state: stateMerge([nodeList && nodeList.state, metricsList && metricsList.state, topologyElement ? stateType.SUCCESS : stateType.LOADING]),
    nodeList
  }
}

function mapActionsToProps(dispatch, ownProps){
  const needNodeList = forced => dispatch(nodeActions.needNodesForTopology(ownProps.topologyId, forced));
  const needMetricsList = forced => dispatch(metricsActions.needTopologyMetrics(ownProps.topologyId, forced));
  return {
    needNodeList,
    needMetricsList,
    notLoadedCallback: () => {
      needNodeList(false);
      needMetricsList(false);
    }
  }
}

export default connect(mapStateToProps, mapActionsToProps)(StateComponent(TopologyNodeMetricsContainer));