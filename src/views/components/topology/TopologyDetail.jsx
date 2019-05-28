import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import processes from 'rootApp/enums/processes';
import * as topologyActions from 'rootApp/actions/topologyActions';
import * as applicationActions from 'rootApp/actions/applicationActions';

import TopologyNodeMetricsContainer from 'components/node/TopologyNodeMetricsContainer';
import TopologyNodeGraphsContainer from 'components/node/TopologyNodeGraphsContainer';

import './TopologyDetail.less';
import {menuItemType} from 'rootApp/types';
import TopologySchemaPanel from './TopologySchemaPanel';
import config from 'rootApp/config';

class TopologyDetail extends React.Component {
  constructor(props) {
    super(props);
    this.schemaImported = this.schemaImported.bind(this);
    this._actions = {
      nodes: null,
      schema: null,
      nodeMetrics: null
    };
    this.rangeIntervalId = null;
  }

  componentDidMount() {
    const {metricsRefreshInterval} = config.params;
    if (config.params.metricsRefreshInterval) {
      this.rangeIntervalId = setInterval(() => {
        const {metricsRange, changeMetricsRange} = this.props;
        if (metricsRange) {
          const newSince = (new Date((new Date(metricsRange.since)).getTime() + metricsRefreshInterval)).toISOString();
          const newTill = (new Date((new Date(metricsRange.till)).getTime() + metricsRefreshInterval)).toISOString();
          changeMetricsRange(newSince, newTill, metricsRange.since, metricsRange.till);
        }
      }, metricsRefreshInterval);
    }
  }

  componentWillUnmount() {
    this.rangeIntervalId && clearInterval(this.rangeIntervalId);
    this.rangeIntervalId = null;
  }

  componentWillMount(){
    this._sendActions(this.props);
  }

  componentWillReceiveProps(props){
    this._sendActions(props);
  }

  setActions(tab, actions){
    this._actions[tab] = actions;
    this._sendActions(this.props);
  }

  _sendActions(props){
    const {topology, setActions, testTopology, editTopology, edit, clone, publish, topologyDelete, topologyId, onChangeTab, activeTab} = props;
    const otherActions = {
      type: menuItemType.SUB_MENU,
      caption: '...',
      items: [],
      noCaret: true
    };
    const pageActions = [
      {
        caption: 'Nodes',
        icon: 'fa fa-tasks',
        type: menuItemType.ACTION,
        action: () => onChangeTab('nodes'),
        color: activeTab === 'nodes' ? 'info' : 'default',
        round: true
      },
      {
        caption: 'Schema',
        icon: 'fa fa-edit',
        type: menuItemType.ACTION,
        action: () => onChangeTab('schema'),
        color: activeTab === 'schema' ? 'info' : 'default',
        round: true
      },
      {
        caption: 'Graphs',
        icon: 'fa fa-area-chart',
        type: menuItemType.ACTION,
        action: () => onChangeTab('graphs'),
        color: activeTab === 'graphs' ? 'info' : 'default',
        round: true
      }
    ];
    if (edit){
      otherActions.items.push({caption: 'Edit', action: edit});
    }
    if (testTopology) {
      otherActions.items.push({
        caption: 'Test',
        action: testTopology,
        processId: processes.topologyTest(topologyId)
      });
    }

    if (topology.visibility !== 'draft') {
      if (topology.enabled) {
        otherActions.items.push({
          caption: 'Disable',
          action: () => editTopology(false),
          processId: processes.topologyUpdate(topologyId),
        });
      } else {
        otherActions.items.push({
          caption: 'Enable',
          action: () => editTopology(true),
          processId: processes.topologyUpdate(topologyId),
        });
      }
    }

    if (publish){
      otherActions.items.push({
        caption: 'Publish',
        action: publish,
        processId: processes.topologyPublish(topologyId),
        disabled: !topology || topology.visibility === 'public'
      });
    }
    if (clone){
      otherActions.items.push({
        caption: 'Clone',
        processId: processes.topologyClone(topologyId),
        action: clone
      })
    }
    if (topologyDelete){
      const deleteDisabled = !topology || (topology.visibility === 'public' && topology.enabled);
      otherActions.items.push({
        caption: 'Delete',
        processId: processes.topologyDelete(topologyId),
        action: topologyDelete,
        disabled: deleteDisabled,
        tooltip: deleteDisabled ? 'Disable topology first' : null
      });
    }
    if (this._actions['schema']){
      this._actions['schema'].forEach(menuItem => {
        if (menuItem.type !== menuItemType.SUB_MENU){
          pageActions.push(menuItem);
        } else {
          otherActions.items.push({type: menuItemType.SEPARATOR});
          otherActions.items.push(...menuItem.items);
        }
      });
    }
    pageActions.push(otherActions);
    setActions(pageActions);
  }

  schemaImported(msg){
    this.props.onChangeTab('schema');
  }

  render() {
    const {topologyId, activeTab, setActions, topology, onChangeTopology, componentKey, metricsRange, altMetricsRange, interval, pageId} = this.props;
    const schemaVisible = activeTab === 'schema';
    const newComponentKey = `${componentKey}.${topologyId}`;
    document.title = `${topology.name}.v${topology.version} | Pipes Manager`;

    return (
      <div className="topology-detail">
        <div className="tab-content">
          {activeTab === 'nodes' && <TopologyNodeMetricsContainer pageId={pageId} topologyId={topologyId} componentKey={`${newComponentKey}.metrics`} metricsRange={metricsRange} altMetricsRange={altMetricsRange} />}
          {activeTab === 'graphs' && <TopologyNodeGraphsContainer pageId={pageId} topologyId={topologyId} componentKey={`${newComponentKey}.graphs`} metricsRange={metricsRange} altMetricsRange={altMetricsRange} interval={interval} />}
          <div className={'schema-wrapper' + ( schemaVisible ? '' : ' hidden')}>
            <TopologySchemaPanel
              pageId={pageId}
              componentKey={`${newComponentKey}.schema`}
              metricsRange={metricsRange}
              schemaId={topologyId}
              topologyId={topologyId}
              setActions={this.setActions.bind(this, 'schema')}
              onChangeTopology={onChangeTopology}
              visible={schemaVisible}
              onImport={this.schemaImported}
            />
          </div>
        </div>
      </div>
    );
  }
}

TopologyDetail.defaultProps = {
  activeTab: 'nodes',
  componentKey: PropTypes.string.isRequired
};

TopologyDetail.propTypes = {
  topologyId: PropTypes.string.isRequired,
  topology: PropTypes.object,
  pageId: PropTypes.string.isRequired,
  activeTab: PropTypes.string.isRequired,
  onChangeTab: PropTypes.func.isRequired,
  setActions: PropTypes.func.isRequired,
  onChangeTopology: PropTypes.func.isRequired,
  testTopology: PropTypes.func,
  altMetricsRange: PropTypes.object,
  metricsRange: PropTypes.object,
};

function mapStateToProps(state, ownProps) {
  return {
  }
}

function mapActionsToProps(dispatch, ownProps){
  const {topologyId} = ownProps;
  return {
    edit: () => dispatch(applicationActions.openModal('topology_edit', {topologyId})),
    editTopology: enabled => dispatch(topologyActions.topologyUpdate(ownProps.topologyId, { enabled })),
    testTopology: () => {
      dispatch(topologyActions.testTopology(ownProps.topologyId)).then(result => {
        if (result){
          ownProps.onChangeTab('nodes');
        }
        return result;
      })
    },
    clone: () => dispatch(topologyActions.cloneTopology(topologyId)).then(topology => {
      if (topology && topology._id !== topologyId){
        ownProps.onChangeTopology(topology._id);
      }
    }),
    topologyDelete: () => dispatch(applicationActions.openModal('topology_delete_dialog', {topologyId, redirectToList: true})),
    publish: () => dispatch(topologyActions.publishTopology(topologyId)),
    changeMetricsRange: (since, till, altSince, altTill) => dispatch(applicationActions.setPageArgs(ownProps.pageId, {
      metricsRange: {since, till},
      altMetricsRange: altSince && altTill ? {since: altSince, till: altTill} : null
    }))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(TopologyDetail);
