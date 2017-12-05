import React from 'react'
import PropTypes from 'prop-types';
import TreeView from 'elements/treeView/TreeView';
import {connect} from 'react-redux';
import * as categoryActions from 'rootApp/actions/categoryActions';
import StateComponent from 'rootApp/views/wrappers/StateComponent';
import {stateType} from 'rootApp/types';

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
    state: tree && tree.state,
    root: tree && tree.state == stateType.SUCCESS ? treeItemToTreeView(category.elements, tree.root, tree.selectedId) : null
  }
}

function mapActionsToProps(dispatch, ownProps){
  return {
    notLoadedCallback: () => dispatch(categoryActions.needCategoryTree(ownProps.componentKey, false, ownProps.initSelectedId)),
    onItemClick: itemId => dispatch(categoryActions.treeItemClick(ownProps.componentKey, itemId, ownProps.onSelect)),
    editAction: (id, name) => dispatch(categoryActions.updateCategory(id, {name})),
    createAction: parentId => dispatch(categoryActions.createCategory({parent: parentId, name: 'New category'})),
    deleteAction: id => dispatch(categoryActions.deleteCategory(id))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(StateComponent(TreeView));