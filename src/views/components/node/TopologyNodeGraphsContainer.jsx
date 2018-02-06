import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import StateComponent from 'wrappers/StateComponent';
import TopologyMetrics from 'components/metrics/TopologyMetrics';

import * as applicationActions from 'actions/applicationActions';
import * as nodeActions from 'actions/nodeActions';
import * as metricsActions from 'actions/metricsActions';
import stateMerge from 'rootApp/utils/stateMerge';
import {stateType} from 'rootApp/types';
import ProcessChartPanel from 'components/topology/charts/ProcessChartPanel';
import ProcessTimeChartPanel from 'components/topology/charts/ProcessTimeChartPanel';
import WaitingTimeChartPanel from 'components/topology/charts/WaitingTimeChartPanel';
import QueueDepthChartPanel from 'components/topology/charts/QueueDepthChartPanel';


class TopologyNodeGraphsContainer extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {componentKey, topologyId, changeMetricsRange, changeMetricsInterval, metricsRange, interval, metricsList, nodeElements, suffix, metricsElements} = this.props;
    const nodeNames = metricsList.items.map(id => nodeElements[id].name);
    const nodeMetrics = metricsList.items.map(id => metricsElements[id + suffix].data);
    const processTimeValues = nodeMetrics.map(metrics => metrics.process_time.avg);
    const waitingTimeValues = nodeMetrics.map(metrics => metrics.waiting_time.avg);
    const queueDepthValues = nodeMetrics.map(metrics => metrics.queue_depth.max);
    return (
      <div>
        <TopologyMetrics
          topologyId={topologyId}
          componentKey={`${componentKey}.${topologyId}.metrics`}
          metricsRange={metricsRange}
          interval={interval}
          changeMetricsRange={changeMetricsRange}
          changeMetricsInterval={changeMetricsInterval}
        />
        <ProcessChartPanel requests={metricsList.data.requests} componentKey={`${componentKey}.${topologyId}.process-chart`}/>
        <ProcessTimeChartPanel keys={nodeNames} values={processTimeValues} componentKey={`${componentKey}.${topologyId}.process-time-chart`}/>
        <WaitingTimeChartPanel keys={nodeNames} values={waitingTimeValues} componentKey={`${componentKey}.${topologyId}.waiting-time-chart`}/>
        <QueueDepthChartPanel keys={nodeNames} values={queueDepthValues} componentKey={`${componentKey}.${topologyId}.queue-depth-chart`}/>
      </div>
    );
  }
}

TopologyNodeGraphsContainer.propTypes = {};

function mapStateToProps(state, ownProps){
  const {node, metrics, topology} = state;
  const suffix = `[${ownProps.interval}]` + (ownProps.metricsRange ? `[${ownProps.metricsRange.since}-${ownProps.metricsRange.till}]` : '');
  const key = `${ownProps.topologyId}` + suffix;
  const nodeList = node.lists['@topology-' + ownProps.topologyId];
  const metricsList = metrics.topologies[key];
  const topologyElement = topology.elements[ownProps.topologyId];
  return {
    state: stateMerge([nodeList && nodeList.state, metricsList && metricsList.state, topologyElement ? stateType.SUCCESS : stateType.LOADING]),
    metricsList,
    nodeElements: node.elements,
    suffix,
    metricsElements: metrics.elements
  };
}

function mapActionsToProps(dispatch, ownProps){
  const needNodeList = forced => dispatch(nodeActions.needNodesForTopology(ownProps.topologyId, forced));
  const needMetricsList = forced => dispatch(metricsActions.needTopologyMetricsWithRequests(ownProps.topologyId, ownProps.interval, ownProps.metricsRange, forced));
  return {
    notLoadedCallback: () => {
      needNodeList(false);
      needMetricsList(false);
    },
    changeMetricsRange: (since, till) => dispatch(applicationActions.setPageArgs({metricsRange: {since, till}})),
    changeMetricsInterval: interval => dispatch(applicationActions.setPageArgs({interval}))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(StateComponent(TopologyNodeGraphsContainer));