import React from 'react';
import { connect } from 'react-redux';
import Modal from '../wrappers/Modal';
import SdkImlChangeForm from '../components/sdkImpls/SdkImlChangeForm';

function mapStateToProps(state, { componentKey }) {
  return {
    form: componentKey,
  };
}

export default connect(mapStateToProps)(Modal(SdkImlChangeForm, {
  title: 'Change Service',
  submitCaption: 'Change',
  closeCaption: 'Cancel',
  size: 'lg'
}));
