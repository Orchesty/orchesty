import React from 'react'
import {Field, reduxForm} from 'redux-form'
import { connect } from 'react-redux';
import PropTypes from 'prop-types';

import * as authActions from '../../../actions/authActions';
import * as applicationActions from '../../../actions/applicationActions';

import TextInput from '../../elements/input/TextInput';
import PasswordInput from '../../elements/input/PasswordInput';
import StateButton from '../../elements/input/StateButton';
import NonAuthPage from '../../wrappers/NonAuthPage';

const processId = 'login';

class LoginPage extends React.Component {
  constructor(props) {
    super(props);
    this.onSubmit = this.onSubmit.bind(this);
    this.registrationClick = this.registrationClick.bind(this);
    this.resetPasswordClick = this.resetPasswordClick.bind(this);
  }

  onSubmit(data){
    this.props.login(data);
  }

  registrationClick(e){
    e.preventDefault();
    this.props.switchToRegistration();
  }

  resetPasswordClick(e){
    e.preventDefault();
    this.props.switchToResetPassword();
  }

  render() {
    return (
      <form onSubmit={this.props.handleSubmit(this.onSubmit)}>
        <h1>Login</h1>
        <div>
          <Field name="email" component={TextInput} label="Email" />
        </div>
        <div>
          <Field name="password" component={PasswordInput} label="Password" />
        </div>
        <div>
          <StateButton type="submit" color="default" state={this.props.processState}>Log in</StateButton>
          <a className="reset_pass" href="#" onClick={this.resetPasswordClick}>Lost your password?</a>
        </div>

        <div className="clearfix" />

        <div className="separator">
          <p className="change_link">New to site?
            <a href="#" className="to_register" onClick={this.registrationClick}> Create Account </a>
          </p>

          <div className="clearfix" />
          <br />

          <div>
            <h1 className="no-line"><i className="fa fa-paw" /> Gentelella Alela!</h1>
            <p>©2016 All Rights Reserved. Gentelella Alela! is a Bootstrap 3 template. Privacy and Terms</p>
          </div>
        </div>
      </form>
    );
  }
}

LoginPage.propTypes = {
  processState: PropTypes.string,
  login: PropTypes.func.isRequired,
  switchToRegistration: PropTypes.func.isRequired,
  switchToResetPassword: PropTypes.func.isRequired
};

function validate(values){
  const errors = {};
  if (!values.email) {
    errors.email = 'Email is required';
  }
  if (!values.password) {
    errors.password = 'Password is required';
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
    login: data => dispatch(authActions.login(data, processId)),
    switchToRegistration: () => dispatch(applicationActions.selectPage('registration')),
    switchToResetPassword: () => dispatch(applicationActions.selectPage('reset_password'))
  }
}

export default NonAuthPage(connect(mapStateToProps, mapActionsToProps)(reduxForm({validate, form: 'login'})(LoginPage)));