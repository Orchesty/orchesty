import React from 'react'
import { connect } from 'react-redux';

import * as cronTasksActions from 'actions/cronTaskActions';
import Page from 'wrappers/Page';
import Panel from 'wrappers/Panel';
import CronTasksListTable from '../components/cronTask/CronTasksListTable';

function mapStateToProps(state, ownProps) {
  const { cronTask } = state;
  const list = cronTask.lists[ownProps.componentKey];
  return {
    list: list,
    elements: cronTask.elements,
    state: list && list.state,
  }
}

function mapActionsToProps(dispatch, ownProps) {
  const needList = forced => dispatch(cronTasksActions.needCronTaskList(ownProps.componentKey));
  return {
    needList: needList,
    notLoadedCallback: needList,
    initialize: () => dispatch(cronTasksActions.cronTaskInitialize()),
  }
}

export default Page(Panel(connect(mapStateToProps, mapActionsToProps)(CronTasksListTable), { title: 'Cron Tasks' }));
