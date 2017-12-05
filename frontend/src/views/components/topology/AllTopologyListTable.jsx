import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';

import * as topologyActions from 'actions/topologyActions';
import * as applicationActions from 'actions/applicationActions';

import TopologyListTable from './TopologyListTable';

function mapStateToProps(state, ownProps){
  const {topology} = state;
  const list = topology.lists[ownProps.componentKey];
  return {
    list: list,
    elements: topology.elements,
    state: list && list.state
  }
}

function mapActionsToProps(dispatch, ownProps){
  const needList = (forced = false) => dispatch(topologyActions.needTopologyList(ownProps.componentKey, ownProps.filter));
  return {
    needList,
    notLoadedCallback: needList,
    listChangeSort: (sort) => dispatch(topologyActions.topologyListChangeSort(ownProps.componentKey, sort)),
    listChangePage: (page) => dispatch(topologyActions.topologyListChangePage(ownProps.componentKey, page)),
    listChangeFilter: (filter) => dispatch(topologyActions.topologyListChangeFilter(ownProps.componentKey, filter)),
    openModal: (id, data) => dispatch(applicationActions.openModal(id, data)),
    openNewTopology: () => dispatch(applicationActions.openModal('topology_edit', {addNew: true})),
    selectPage: (key, args) => dispatch(applicationActions.selectPage(key, args)),
    clone: id => dispatch(topologyActions.cloneTopology(id)),
    topologyDelete: id => dispatch(applicationActions.openModal('topology_delete_dialog', {topologyId: id})),
    publish: id => dispatch(topologyActions.publishTopology(id)),
    changeCategory: id => dispatch(applicationActions.openModal('category_topology_change', {topologyId: id}))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(TopologyListTable);