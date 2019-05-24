import React from 'react'
import { connect } from 'react-redux';

import * as applicationActions from 'rootApp/actions/applicationActions';
import * as notificationSettingsActions from 'actions/notificationSettingsActions';
import Page from 'wrappers/Page';
import Panel from 'wrappers/Panel';
import NotificationSettingsListTable from '../components/notificationSettings/NotificationSettingsListTable';

function mapStateToProps(state, ownProps) {
  const { notificationSettings } = state;
  const list = notificationSettings.lists[ownProps.componentKey];
  return {
    list: list,
    elements: notificationSettings.elements,
    state: list && list.state,
  }
}

function mapActionsToProps(dispatch, ownProps) {
  const needList = forced => dispatch(notificationSettingsActions.needNotificationSettingList(ownProps.componentKey));
  return {
    needList: needList,
    notLoadedCallback: needList,
    initialize: () => dispatch(notificationSettingsActions.notificationSettingInitialize()),
    changeNotificationSettings: (id, data) => dispatch(applicationActions.openModal('notification_settings_change', { componentKey: ownProps.componentKey, id, data })),
  }
}

export default Page(Panel(connect(mapStateToProps, mapActionsToProps)(NotificationSettingsListTable), { title: 'Notification Settings' }));
