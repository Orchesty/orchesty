import React from 'react';
import { connect } from 'react-redux';

import Modal from 'rootApp/views/wrappers/Modal';
import HumanTaskChangeForm from 'rootApp/views/components/humanTask/HumanTaskChangeForm';

function mapStateToProps(state, ownProps) {
  return {
    form: ownProps.componentKey,
  };
}

export default connect(mapStateToProps)(Modal(HumanTaskChangeForm, {
  title: 'Change human task',
  submitCaption: 'Change',
  closeCaption: 'Cancel',
  size: 'lg'
}));
