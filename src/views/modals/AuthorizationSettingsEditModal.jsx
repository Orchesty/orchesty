import React from 'react'
import PropTypes from 'prop-types';

import {stateType} from 'rootApp/types';
import AuthorizationSettingsForm from 'components/authorization/AuthorizationSettingsForm';
import StateButton from 'elements/input/StateButton';
import processes from "enums/processes";

class AuthorizationSettingsEditModal extends React.Component {
  constructor(props) {
    super(props);
    this.closeClick = this.closeClick.bind(this);
    this.close = this.close.bind(this);
    this.makeSubmit = this.makeSubmit.bind(this);
    this.setSubmit = this.setSubmit.bind(this);
    this._submitForm = null;
  }

  closeClick(e){
    e.preventDefault();
    this.close();
  }

  close(){
    this.props.onCloseModal(this);
  }

  makeSubmit(){
    if (this.props.processState != stateType.LOADING){
      this._submitForm();
    }
  }

  setSubmit(submit){
    this._submitForm = submit;
  }

  render() {
    const {authorizationId} = this.props;
    const formKey = 'authorization.settings.' + authorizationId;
    const processId = processes.authorizationSaveSettings(authorizationId);
    return (
      <div className="modal fade in" tabIndex="-1" role="dialog" aria-hidden="true" style={{display: 'block', paddingRight: '17px'}}>
        <div className="modal-dialog modal-md">
          <div className="modal-content">
            <div className="modal-header">
              <button type="button" className="close" onClick={this.closeClick}><span aria-hidden="true">×</span></button>
              <h4 className="modal-title" id="myModalLabel">Edit authorization settings</h4>
            </div>
            <div className="modal-body">
              <AuthorizationSettingsForm
                form={formKey}
                setSubmit={this.setSubmit}
                authorizationId={authorizationId}
                onSuccess={this.close}
              />
            </div>
            <div className="modal-footer">
              <button type="button" className="btn btn-default" onClick={this.closeClick}>Close</button>
              <StateButton type="button" color="primary" processId={processId} onClick={this.makeSubmit}>Save changes</StateButton>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

AuthorizationSettingsEditModal.propTypes = {
  authorizationId: PropTypes.string.isRequired,
  onCloseModal: PropTypes.func.isRequired
};


export default AuthorizationSettingsEditModal;