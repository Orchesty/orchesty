import React from 'react'
import { connect } from 'react-redux';

import * as humanTasksActions from 'actions/humanTaskActions';
import Page from 'wrappers/Page';
import Panel from 'wrappers/Panel';
import HumanTasksListTable from 'components/humanTask/HumanTasksListTable';
import * as applicationActions from 'rootApp/actions/applicationActions';

function mapStateToProps(state, ownProps) {
  const { humanTask } = state;
  const list = humanTask.lists[ownProps.componentKey];
  return {
    list: list,
    elements: humanTask.elements,
    state: list && list.state,
  }
}

function mapActionsToProps(dispatch, ownProps) {
  const needList = forced => dispatch(humanTasksActions.needHumanTaskList(ownProps.componentKey));
  return {
    needList,
    notLoadedCallback: needList,
    listChangePage: (page) => dispatch(humanTasksActions.humanTaskListChangePage(ownProps.componentKey, page)),
    process: (topology, node, token, approve) => dispatch(humanTasksActions.humanTaskProcess(ownProps.componentKey, topology, node, token, approve, {})),
    listChangeSort: (sort) => dispatch(humanTasksActions.humanTaskChangeSort(ownProps.componentKey, sort)),
    listChangeFilter: (filter) => dispatch(humanTasksActions.humanTaskListChangeFilter(ownProps.componentKey, filter)),
    initialize: () => dispatch(humanTasksActions.humanTaskInitialize()),
    approveHumanTask: (topology, node, token, approve) => dispatch(applicationActions.openModal('human_task_approve', { componentKey: ownProps.componentKey, topology, node, token, approve })),
    changeHumanTask: (id, data) => dispatch(applicationActions.openModal('human_task_change', { componentKey: ownProps.componentKey, id, data })),
  }
}

export default Page(Panel(connect(mapStateToProps, mapActionsToProps)(HumanTasksListTable), { title: 'Human Tasks' }));
