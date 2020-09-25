import React from 'react'
import {Field, reduxForm} from 'redux-form'
import {connect} from 'react-redux';
import PropTypes from 'prop-types';

import * as authActions from 'actions/authActions';
import * as applicationActions from 'actions/applicationActions';

import TextInput from 'elements/input/TextInput';
import PasswordInput from 'elements/input/PasswordInput';
import StateButton from 'elements/input/StateButton';
import NonAuthPage from 'wrappers/NonAuthPage';
import processes from "rootApp/enums/processes";

class LoginPage extends React.Component {
  constructor(props) {
    super(props);
    this.onSubmit = this.onSubmit.bind(this);
    this.registrationClick = this.registrationClick.bind(this);
    this.resetPasswordClick = this.resetPasswordClick.bind(this);
  }

  onSubmit(data) {
    this.props.login(data);
  }

  registrationClick(e) {
    e.preventDefault();
    this.props.switchToRegistration();
  }

  resetPasswordClick(e) {
    e.preventDefault();
    this.props.switchToResetPassword();
  }

  render() {
    const {componentKey} = this.props;
    return (
      <form onSubmit={this.props.handleSubmit(this.onSubmit)}>
        <h1>Login</h1>
        <div>
          <Field name="email" component={TextInput} label="Email"/>
        </div>
        <div>
          <Field name="password" component={PasswordInput} label="Password"/>
        </div>
        <div>
          <StateButton type="submit" color="default" processId={processes.authLogin(componentKey)}>Log in</StateButton>
          {/*<a className="reset_pass" href="#" onClick={this.resetPasswordClick}>Lost your password?</a>*/}
        </div>

        <div className="clearfix"/>

        {/*<div className="separator">*/}
        {/*  <p className="change_link">New to site?*/}
        {/*    <a href="#" className="to_register" onClick={this.registrationClick}> Create Account </a>*/}
        {/*  </p>*/}
        {/*  <div className="clearfix" />*/}
        {/*</div>*/}
      </form>
    );
  }
}

LoginPage.propTypes = {
  login: PropTypes.func.isRequired,
  switchToRegistration: PropTypes.func.isRequired,
  switchToResetPassword: PropTypes.func.isRequired,
  componentKey: PropTypes.string.isRequired
};

function validate(values) {
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
  return {};
}

function mapActionsToProps(dispatch, ownProps) {
  return {
    login: data => dispatch(authActions.login(data, ownProps.componentKey)),
    switchToRegistration: () => dispatch(applicationActions.openPage('registration')),
    switchToResetPassword: () => dispatch(applicationActions.openPage('reset_password'))
  }
}

export default NonAuthPage(connect(mapStateToProps, mapActionsToProps)(reduxForm({
  validate,
  form: 'login'
})(LoginPage)));