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
  }

  changeTab(tab, index){
    this.props.onChangeTab(tab.id);
  }

  render() {
    const {topologyId, activeTab, setActions, onChangeTopology} = this.props;
    let activeIndex = tabItems.findIndex(tab => activeTab == tab.id);
    return (
      <div className="topology-detail">
        <TabBar items={tabItems} active={activeIndex} onChangeTab={this.changeTab} />
        {activeTab == 'nodes' && <TopologyNodeListTable topologyId={topologyId} />}
        <div className={'schema-wrapper' + (activeTab != 'schema' ? ' hidden' : '')}>
          <TopologySchema schemaId={topologyId} setActions={setActions} onChangeTopology={onChangeTopology} />
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
  onChangeTab: PropTypes.func.isRequired
};

export default TopologyDetail;