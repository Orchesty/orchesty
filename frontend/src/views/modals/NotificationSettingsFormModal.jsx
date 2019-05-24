import React from 'react';
import { connect } from 'react-redux';
import NotificationSettingsForm from '../components/notificationSettings/NotificationSettingsForm';
import Modal from '../wrappers/Modal';

function mapStateToProps(state, ownProps) {
  return {
    form: ownProps.componentKey,
  };
}

export default connect(mapStateToProps)(Modal(NotificationSettingsForm, {
  title: 'Change notification settings',
  submitCaption: 'Change',
  closeCaption: 'Cancel',
  size: 'lg'
}));
