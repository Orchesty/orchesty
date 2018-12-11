import React from 'react';
import { connect } from 'react-redux';

import Modal from 'rootApp/views/wrappers/Modal';
import HumanTaskApproveForm from 'rootApp/views/components/humanTask/HumanTaskRunForm';

function mapStateToProps(state, ownProps) {
  return {
    form: ownProps.componentKey,
  };
}

export default connect(mapStateToProps)(Modal(HumanTaskApproveForm, {
  title: 'Approve human task',
  submitCaption: 'Approve',
  closeCaption: 'Cancel',
  size: 'lg'
}));