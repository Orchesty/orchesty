import React from 'react'
import PropTypes from 'prop-types';
import Panel from 'rootApp/views/wrappers/Panel';
import {connect} from 'react-redux';

import './NodeMetrics.less';
import {menuItemType} from 'rootApp/types';
import StateComponent from 'rootApp/views/wrappers/StateComponent';
import * as applicationActions from 'rootApp/actions/applicationActions';
import * as nodeActions from 'rootApp/actions/nodeActions';

class NodeMetrics extends React.Component {
  constructor(props) {
    super(props);
  }

  componentWillMount(){
    this.sendActions();
  }

  sendActions(){
    const {node, setActions, metrics, tests, topology, runNode, updateNode} = this.props;
    const actions = [];
    if (metrics.data.process.errors){
      actions.push({
        type: menuItemType.BADGE,
        icon: 'bg-red white',
        caption: metrics.data.process.errors
      });
    }
    const test = tests[node._id];
    if (test){
      actions.push({
        type: menuItemType.TEXT,
        icon: test.code == 200 ? 'fa fa-check green' : 'fa fa-warning',
        caption: test.message
      });
    }
    if (node.handler == 'event'){
      if (topology.enabled){
        actions.push({
          type: menuItemType.ACTION,
          icon: 'fa fa-play',
          caption: 'Run',
          action: runNode
        });
      }
      actions.push({
        type: menuItemType.ACTION,
        icon: 'fa fa-power-off',
        caption: node.enabled ? 'Disable' : 'Enable',
        action: () => {
          updateNode(node._id, {enabled: !node.enabled})
        }
      });
    }

    setActions(actions);
  }

  render() {
    const {metrics: {data}} = this.props;
    return (
      <div className="node-metrics tile_count">
        <div className="tile_stats_count">
          <span className="count_top">Total Processes</span>
          <div className="count">{data.process.total}</div>
          <span className="count_bottom red">Failed: {data.process.errors}</span>
        </div>
        <div className="tile_stats_count">
          <span className="count_top">Queue Depth</span>
          <div className="count">{data.queue_depth.min}</div>
          <span className="count_bottom red">Max: {data.queue_depth.max}</span>
        </div>
        <div className="tile_stats_count">
          <span className="count_top">Waiting Time</span>
          <div className="count">{data.waiting_time.avg}</div>
          <span className="count_bottom green">Min: {data.waiting_time.min}</span> | <span className="count_bottom red">Max: {data.waiting_time.max}</span>
        </div>
        <div className="tile_stats_count">
          <span className="count_top">Process Time</span>
          <div className="count">{data.process_time.avg}</div>
          <span className="count_bottom green">Min: {data.process_time.min}</span> | <span className="count_bottom red">Max: {data.process_time.max}</span>
        </div>
        <div className="tile_stats_count">
          <span className="count_top">CPU Time</span>
          <div className="count">{data.cpu_time.avg}</div>
          <span className="count_bottom green">Min: {data.cpu_time.min}</span> | <span className="count_bottom red">Max: {data.cpu_time.max}</span>
        </div>
        <div className="tile_stats_count">
          <span className="count_top">Request Time</span>
          <div className="count">{data.request_time.avg}</div>
          <span className="count_bottom green">Min: {data.request_time.min}</span> | <span className="count_bottom red">Max: {data.request_time.max}</span>
        </div>
      </div>
    );
  }
}

NodeMetrics.propTypes = {
  setActions: PropTypes.func.isRequired,
  metrics: PropTypes.object.isRequired,
  tests: PropTypes.object,
  topology: PropTypes.object.isRequired,
  runNode: PropTypes.func,
  updateNode: PropTypes.func
};

function mapStateToProps(state, ownProps){
  const {node, metrics, topology} = state;
  const nodeElement = node.elements[ownProps.nodeId];
  const metricsElement = metrics.elements[ownProps.nodeId];
  return {
    state: metricsElement && metricsElement.state,
    node: nodeElement,
    metrics: metricsElement,
    title: nodeElement.name,
    subTitle: `${nodeElement.type} ${nodeElement.handler}`,
    tests: node.tests,
    topology: topology.elements[nodeElement.topology_id]
  }
}

function mapActionsToProps(dispatch, ownProps){
  return {
    updateNode: data => dispatch(nodeActions.nodeUpdate(ownProps.nodeId, data)),
    runNode:() => dispatch(applicationActions.openModal('node_run', {nodeId: ownProps.nodeId})),
  }
}


export default connect(mapStateToProps, mapActionsToProps)(Panel(StateComponent(NodeMetrics)));