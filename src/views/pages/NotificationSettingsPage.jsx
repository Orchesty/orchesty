import React from 'react';
import {connect} from 'react-redux';

import NotificationSettingsForm from 'components/notificationSettings/NotificationSettingsForm';
import Page from 'wrappers/Page';
import StateComponent from 'wrappers/StateComponent';
import * as notificationSettingsActions from 'actions/notificationSettingsActions';
import processes from 'enums/processes';
import Panel from 'wrappers/Panel';

function mapStateToProps(state, ownProps) {
  const {notificationSettings} = state;
  return {
    initialValues: notificationSettings.data,
    state: notificationSettings.state,
    pageTitle: 'Notification settings',
    form: ownProps.componentKey,
    processId: processes.notificationSettingsUpdate()
  }
}

function mapActionsToProps(dispatch, ownProps){
  return {
    notLoadedCallback: () => dispatch(notificationSettingsActions.needNotificationSettings()),
    commitAction: data => dispatch(notificationSettingsActions.updateNotificationSettings(data))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(Page(Panel(StateComponent(NotificationSettingsForm), {title: 'Notification setting'})));
