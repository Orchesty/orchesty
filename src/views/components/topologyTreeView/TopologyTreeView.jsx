import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import './topologyTreeView.less';
import {stateType} from 'rootApp/types';
import StateComponent from 'wrappers/StateComponent';
import * as categoryActions from 'actions/categoryActions';
import TopologyTreeViewList from 'components/topologyTreeView/TopologyTreeViewList';
import stateMerge from 'rootApp/utils/stateMerge';
import * as topologyActions from 'rootApp/actions/topologyActions';
import * as applicationActions from 'rootApp/actions/applicationActions';

class TopologyTreeView extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {root, toggleCategory, openTopology, openContextMenu} = this.props;
    return (
      <div className="topology-tree-view">
        {root ? <TopologyTreeViewList category={root} toggleCategory={toggleCategory} openTopology={openTopology} openContextMenu={openContextMenu} topLevel /> : null}
      </div>
    );
  }
}

TopologyTreeView.propTypes = {
  root: PropTypes.object,
  toggleCategory: PropTypes.func.isRequired
};

function treeItemToTreeView(elements, treeItem){
  const item = elements[treeItem.id];
  return {
    id: treeItem.id,
    open: treeItem.open,
    caption: item ? item.name : 'Root',
    children: treeItem.items && treeItem.items.length ? treeItem.items.map(childTreeItem => treeItemToTreeView(elements, childTreeItem)) : []
  }
}

function mapStateToProps(state, ownProps) {
  const {category, topology} = state;
  const tree = category.trees[ownProps.componentKey];
  const topologyList = topology.lists.complete;
  return {
    state: stateMerge([tree && tree.state, topologyList && topologyList.state]),
    root: tree && tree.state == stateType.SUCCESS ? treeItemToTreeView(category.elements, tree.root, tree.selectedId) : null
  }
}

function mapActionsToProps(dispatch, ownProps){
  return {
    notLoadedCallback: () => {
      dispatch(categoryActions.needCategoryTree(ownProps.componentKey, false, undefined, false));
      dispatch(topologyActions.needTopologyList('complete'));
    },
    toggleCategory: id => dispatch(categoryActions.treeToggle(ownProps.componentKey, id)),
    openTopology: id => dispatch(applicationActions.selectPage('topology_detail', {topologyId: id})),
    openContextMenu: (id, type, x, y) => {
      switch (type){
        case 'category':
          return dispatch(applicationActions.openContextMenu('CategoryFileContextMenu', {categoryId: id}, ownProps.componentKey, x, y));
        case 'topology':
          return dispatch(applicationActions.openContextMenu('TopologyFileContextMenu', {topologyId: id}, ownProps.componentKey, x, y));
        default:
          throw Error(`Unknown type [${type}]`);
      }
    }
  }
}

export default connect(mapStateToProps, mapActionsToProps)(StateComponent(TopologyTreeView));