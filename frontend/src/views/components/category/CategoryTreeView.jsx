import React from 'react'
import PropTypes from 'prop-types';
import TreeView from 'elements/treeView/TreeView';
import {connect} from 'react-redux';
import * as categoryActions from 'rootApp/actions/categoryActions';

function treeItemToTreeView(elements, treeItem, selectedId){
  const item = elements[treeItem.id];
  return {
    id: treeItem.id,
    open: treeItem.open,
    caption: item ? item.name : 'Root',
    selected: treeItem.id === selectedId,
    children: treeItem.items && treeItem.items.length ? treeItem.items.map(childTreeItem => treeItemToTreeView(elements, childTreeItem, selectedId)) : null
  }
}

function mapStateToProps(state, ownProps) {
  const {category} = state;
  const tree = category.trees[ownProps.componentKey];
  return {
    root: treeItemToTreeView(category.elements, tree.root, tree.selectedId)
  }
}

function mapActionsToProps(dispatch, ownProps){
  return {
    onItemClick: itemId => dispatch(categoryActions.treeItemClick(ownProps.componentKey, itemId, ownProps.onSelect))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(TreeView);