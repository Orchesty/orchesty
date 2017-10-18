import React from 'react'
import {connect} from 'react-redux';

import AuthorizationSettingsForm from 'components/authorization/AuthorizationSettingsForm';
import processes from "enums/processes";
import Modal from 'rootApp/views/wrappers/Modal';

function mapStateToProps(state, ownProps) {
  return {
    form: ownProps.componentKey + ownProps.authorizationId,
    processId: processes.authorizationSaveSettings(ownProps.authorizationId)
  };
}

export default connect(mapStateToProps)(Modal(AuthorizationSettingsForm, {
  size: 'md',
  title: 'Edit authorization settings'
}));