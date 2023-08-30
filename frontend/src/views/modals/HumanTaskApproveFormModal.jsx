import React from 'react';
import { connect } from 'react-redux';

import Modal from 'rootApp/views/wrappers/Modal';
import HumanTaskApproveForm from 'rootApp/views/components/humanTask/HumanTaskRunForm';
import {makeStartingPointUrl} from "../../services/apiGatewayServer";
import {getHumanTaskRunUrl} from "../../actions/humanTaskActions";

function mapStateToProps(state, { topology, node, token, approve, componentKey }) {
  return {
    form: componentKey,
    subTitle: makeStartingPointUrl(getHumanTaskRunUrl(topology, node, token, approve))
  };
}

export default connect(mapStateToProps)(Modal(HumanTaskApproveForm, {
  title: 'Approve human task',
  submitCaption: 'Approve',
  closeCaption: 'Cancel',
  size: 'lg'
}));