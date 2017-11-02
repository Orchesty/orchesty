import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import processes from 'rootApp/enums/processes';
import * as topologyActions from 'rootApp/actions/topologyActions';
import * as applicationActions from 'rootApp/actions/applicationActions';

import TabBar from 'elements/tab/TabBar';
import TopologyNodeListTable from 'components/node/TopologyNodeListTable';
import TopologySchema from './TopologySchema';

import './TopologyDetail.less';

const tabItems = [
  {
    id: 'nodes',
    caption: 'Nodes'
  },
  {
    id: 'schema',
    caption: 'Schema'
  }
];

class TopologyDetail extends React.Component {
  constructor(props) {
    super(props);
    this.changeTab = this.changeTab.bind(this);
    this.schemaImported = this.schemaImported.bind(this);
    this._actions = {
      nodes: null,
      schema: null
    }
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
    const {topology, setActions, testTopology, edit, clone, publish, topologyDelete, topologyId} = props;
    const pageActions = [];
    if (edit){
      pageActions.push({caption: 'Edit', action: edit});
    }
    if (clone){
      pageActions.push({
        caption: 'Clone',
        processId: processes.topologyClone(topologyId),
        action: clone
      })
    }
    if (publish){
      pageActions.push({
        caption: 'Publish',
        action: publish,
        processId: processes.topologyPublish(topologyId),
        disabled: topology.visibility == 'public'
      });
    }
    if (testTopology) {
      pageActions.push({
        caption: 'Test topology',
        action: testTopology,
        processId: processes.topologyTest(topologyId)
      });
    }
    if (topologyDelete){
      const deleteDisabled = topology.visibility == 'public' && topology.enabled;
      pageActions.push({
        caption: 'Delete',
        processId: processes.topologyDelete(topologyId),
        action: topologyDelete,
        disabled: deleteDisabled,
        tooltip: deleteDisabled ? 'Disable topology first' : null
      });
    }
    if (this._actions['schema']){
      pageActions.push(...this._actions['schema']);
    }
    setActions(pageActions);
  }

  changeTab(tab, index){
    this.props.onChangeTab(tab.id);
  }

  schemaImported(msg){
    this.props.onChangeTab(tabItems[1].id);
  }

  render() {
    const {topologyId, activeTab, setActions, topology, onChangeTopology} = this.props;
    let activeIndex = tabItems.findIndex(tab => activeTab == tab.id);
    const schemaVisible = activeTab == 'schema';
    return (
      <div className="topology-detail">
        <TabBar items={tabItems} active={activeIndex} onChangeTab={this.changeTab}/>
        {activeTab == 'nodes' && <TopologyNodeListTable topologyId={topologyId} setActions={this.setActions.bind(this, 'nodes')}/>}
        <div className={'schema-wrapper' + ( schemaVisible ? '' : ' hidden')}>
          <TopologySchema
            schemaId={topologyId}
            topology={topology}
            setActions={this.setActions.bind(this, 'schema')}
            onChangeTopology={onChangeTopology}
            visible={schemaVisible}
            onImport={this.schemaImported}
          />
        </div>
      </div>
    );
  }
}

TopologyDetail.defaultProps = {
  activeTab: tabItems[0].id
};

TopologyDetail.propTypes = {
  topologyId: PropTypes.string.isRequired,
  topology: PropTypes.object.isRequired,
  activeTab: PropTypes.oneOf(tabItems.map(tab => tab.id)).isRequired,
  onChangeTab: PropTypes.func.isRequired,
  setActions: PropTypes.func.isRequired,
  onChangeTopology: PropTypes.func.isRequired,
  testTopology: PropTypes.func
};

function mapStateToProps(state, ownProps) {
  return {
  }
}

function mapActionsToProps(dispatch, ownProps){
  const {topologyId} = ownProps;
  return {
    edit: () => dispatch(applicationActions.openModal('topology_edit', {topologyId})),
    testTopology: () => {
      dispatch(topologyActions.testTopology(ownProps.topologyId)).then(result => {
        if (result){
          ownProps.onChangeTab(tabItems[0].id);
        }
        return result;
      })
    },
    clone: () => dispatch(topologyActions.cloneTopology(topologyId)).then(topology => {
      if (topology && topology._id != topologyId){
        ownProps.onChangeTopology(topology._id);
      }
    }),
    topologyDelete: () => dispatch(applicationActions.openModal('topology_delete_dialog', {topologyId, redirectToList: true})),
    publish: () => dispatch(topologyActions.publishTopology(topologyId))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(TopologyDetail);