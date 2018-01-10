import React from 'react';
import {connect} from 'react-redux';

import NotificationSettingsForm from 'components/notificationSettings/NotificationSettingsForm';
import Page from 'wrappers/Page';
import StateComponent from 'wrappers/StateComponent';
import * as notificationSettingsActions from 'actions/notificationSettingsActions';
import processes from 'enums/processes';

function mapStateToProps(state, ownProps) {
  const {notificationSettings} = state;
  return {
    initialValues: notificationSettings.data,
    state: notificationSettings.state,
    pageTitle: 'Notification settings',
    form: ownProps.pageKey,
    processId: processes.notificationSettingsUpdate()
  }
}

function mapActionsToProps(dispatch, ownProps){
  return {
    notLoadedCallback: () => dispatch(notificationSettingsActions.needNotificationSettings()),
    commitAction: data => dispatch(notificationSettingsActions.updateNotificationSettings(data))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(Page(StateComponent(NotificationSettingsForm)));
