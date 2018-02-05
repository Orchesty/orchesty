import React from 'react'
import PropTypes from 'prop-types';
import TopologyTreeViewItem from 'rootApp/views/components/topologyTreeView/TopologyTreeViewItem';
import {connect} from 'react-redux';

import './TopologyTreeViewList.less';

class TopologyTreeViewList extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {topLevel, category, topologies, toggleCategory, openTopology, openContextMenu} = this.props;
    const categoriesComp = category.children ? category.children.map(item => <TopologyTreeViewItem toggleCategory={toggleCategory} key={item.id} item={item} objectType="category" topLevel={topLevel} openTopology={openTopology} openContextMenu={openContextMenu}/>) : null;
    const topologiesComp = topologies.map(item => <TopologyTreeViewItem key={item._id} item={item} objectType="topology" topLevel={topLevel} openTopology={openTopology} openContextMenu={openContextMenu} />);
    if ((categoriesComp && categoriesComp.length > 0) || topologiesComp.length) {
      return (
        <ul className={'topology-tree-view-list' + (topLevel ? ' top-level-list' : ' next-level-list')}>
          {categoriesComp}
          {topologiesComp}
        </ul>
      );
    } else {
      return null;
    }
  }
}

TopologyTreeViewList.defaultProps = {
  topLevel: false
};

TopologyTreeViewList.propTypes = {
  category: PropTypes.object.isRequired,
  topologies: PropTypes.array.isRequired,
  topLevel: PropTypes.bool.isRequired,
  toggleCategory: PropTypes.func.isRequired,
  openTopology: PropTypes.func.isRequired,
  openContextMenu: PropTypes.func
};

function mapStateToProps(state, ownProps){
  const {topology} = state;
  const elements = topology.elements;
  return {
    topologies: topology.lists.complete.items
      .filter(id => elements[id].category == ownProps.category.id)
      .map(id => elements[id])
  };
}

export default connect(mapStateToProps)(TopologyTreeViewList);