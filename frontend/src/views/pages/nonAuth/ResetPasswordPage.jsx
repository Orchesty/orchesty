import React from 'react'
import {Field, reduxForm} from 'redux-form'
import { connect } from 'react-redux';
import PropTypes from 'prop-types';

import {stateType} from 'rootApp/types';
import * as authActions from 'actions/authActions';
import * as applicationActions from 'actions/applicationActions';

import TextInput from 'elements/input/TextInput';
import NonAuthPage from 'wrappers/NonAuthPage';
import StateButton from 'elements/input/StateButton';

const processId = 'reset-password';

class ResetPasswordPage extends React.Component {
  constructor(props) {
    super(props);
    this.onSubmit = this.onSubmit.bind(this);
    this.loginClick = this.loginClick.bind(this);
  }

  onSubmit(data){
    this.props.resetPassword(data.email);
  }

  loginClick(e){
    e.preventDefault();
    this.props.switchToLogin();
  }

  render() {
    const {processState} = this.props;
    if (processState != stateType.SUCCESS) {
      return (
        <form onSubmit={this.props.handleSubmit(this.onSubmit)}>
          <h1>Reset password</h1>
          <div>
            <Field name="email" component={TextInput} label="Email"/>
          </div>
          <div>
            <StateButton type="submit" color="default" state={this.props.processState}>Submit</StateButton>
          </div>

          <div className="clearfix" />
          <div className="separator">
            <p className="change_link">Do you remember ?
              <a href="#" className="to_register" onClick={this.loginClick}> Log in </a>
            </p>
            <div className="clearfix" />
          </div>
        </form>
      );
    } else {
      return <p>Email has been send.</p>;
    }
  }
}

ResetPasswordPage.propTypes = {
  processState: PropTypes.string,
  resetPassword: PropTypes.func.isRequired,
  switchToLogin: PropTypes.func.isRequired
};

function validate(values){
  const errors = {};
  if (!values.email) {
    errors.email = 'Email is required';
  }

  return errors;
}

function mapStateToProps(state, ownProps) {
  const {process} = state;
  return {
    processState: process[processId]
  };
}

function mapActionsToProps(dispatch, ownProps){
  return {
    resetPassword: email => dispatch(authActions.resetPassword(email, processId)),
    switchToLogin: () => dispatch(applicationActions.selectPage('login'))
  }
}

export default NonAuthPage(connect(mapStateToProps, mapActionsToProps)(reduxForm({validate, form: 'reset-password'})(ResetPasswordPage)));