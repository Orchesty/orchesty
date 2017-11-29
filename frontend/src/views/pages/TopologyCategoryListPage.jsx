import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import * as categoryActions from 'actions/categoryActions';

import Page from 'wrappers/Page';
import TopologyCategoryList from 'components/topology/TopologyCategoryList';
import StateComponent from 'rootApp/views/wrappers/StateComponent';

function mapStateToProps(state, ownProps){
  const {category} = state;
  const tree = category.trees[ownProps.pageKey];
  return {
    state: tree && tree.state
  }
}

function mapActionsToProps(dispatch, ownProps){
  return {
    notLoadedCallback: forced => dispatch(categoryActions.needCategoryTree(ownProps.pageKey))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(StateComponent(Page(TopologyCategoryList)));