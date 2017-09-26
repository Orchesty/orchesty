import React from 'react'
import PropTypes from 'prop-types';

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
    this._actions = {
      nodes: null,
      schema: null
    }
  }

  setActions(tab, actions){
    const {activeTab, setActions} = this.props;
    this._actions[tab] = actions;
    if (tab == activeTab){
      setActions(actions);
    }
  }

  componentWillReceiveProps(nextProps){
    const {activeTab, setActions} = this.props;
    if (activeTab != nextProps.activeTab){
      setActions(this._actions[nextProps.activeTab]);
    }
  }

  changeTab(tab, index){
    this.props.onChangeTab(tab.id);
  }

  render() {
    const {topologyId, activeTab, setActions, onChangeTopology} = this.props;
    let activeIndex = tabItems.findIndex(tab => activeTab == tab.id);
    const schemaVisible = activeTab == 'schema';
    return (
      <div className="topology-detail">
        <TabBar items={tabItems} active={activeIndex} onChangeTab={this.changeTab}/>
        {activeTab == 'nodes' && <TopologyNodeListTable topologyId={topologyId} setActions={this.setActions.bind(this, 'nodes')}/>}
        <div className={'schema-wrapper' + ( schemaVisible ? '' : ' hidden')}>
          <TopologySchema schemaId={topologyId} setActions={this.setActions.bind(this, 'schema')} onChangeTopology={onChangeTopology} visible={schemaVisible} />
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
  activeTab: PropTypes.oneOf(tabItems.map(tab => tab.id)).isRequired,
  onChangeTab: PropTypes.func.isRequired,
  setActions: PropTypes.func.isRequired,
  onChangeTopology: PropTypes.func.isRequired
};

export default TopologyDetail;