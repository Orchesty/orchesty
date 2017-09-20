import React from 'react'
import { connect } from 'react-redux';

import * as applicationActions from 'actions/applicationActions';

import TopologyEditModal from 'modals/TopologyEditModal';
import AuthorizationSettingsEditModal from 'modals/AuthorizationSettingsEditModal';


class ActiveModal extends React.Component {
  constructor(props) {
    super(props);
    this._close = this.close.bind(this);
  }
  
  close(){
    this.props.closeModal();
  }
  
  render() {
    const {modal, modalData} = this.props;
    switch (modal){
      case 'topology_edit':
        return <TopologyEditModal {...modalData} onCloseModal={this._close} />;

      case 'authorization_settings_edit':
        return <AuthorizationSettingsEditModal {...modalData} onCloseModal={this._close} />;

      default:
        return null;
    }
  }
}

function mapStateToProps(state){
  const {modal, modalData} = state.application;

  return {
    modal,
    modalData
  }
}

function mapActionsToProps(dispatch){
  return {
    closeModal: (id, data) => dispatch(applicationActions.closeModal())
  }
}

export default connect(mapStateToProps, mapActionsToProps)(ActiveModal);