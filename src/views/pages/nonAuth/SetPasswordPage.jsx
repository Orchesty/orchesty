import React from 'react'
import {Field, reduxForm} from 'redux-form'
import {connect} from 'react-redux';
import PropTypes from 'prop-types';

import * as authActions from 'actions/authActions';
import * as applicationActions from 'actions/applicationActions';

import PasswordInput from 'elements/input/PasswordInput';
import NonAuthPage from 'wrappers/NonAuthPage';
import StateButton from 'elements/input/StateButton';
import processes from "rootApp/enums/processes";

class SetPasswordPage extends React.Component {
  constructor(props) {
    super(props);
    this.onSubmit = this.onSubmit.bind(this);
    this.loginClick = this.loginClick.bind(this);
  }

  onSubmit(data) {
    this.props.setPassword(data.password);
  }

  loginClick(e) {
    e.preventDefault();
    this.props.switchToLogin();
  }

  render() {
    const {componentKey} = this.props;
    return (
      <form onSubmit={this.props.handleSubmit(this.onSubmit)}>
        <h1>Set password</h1>
        <div>
          <Field name="password" component={PasswordInput} label="Password"/>
        </div>
        <div>
          <Field name="confirmPassword" component={PasswordInput} label="Confirm password"/>
        </div>
        <div>
          <StateButton type="submit" color="default" processId={processes.authSetPassword(componentKey)}>Submit</StateButton>
        </div>

        <div className="clearfix"/>
        <div className="separator">
          <p className="change_link">Already a member ?
            <a href="#" className="to_register" onClick={this.loginClick}> Log in </a>
          </p>

          <div className="clearfix"/>
        </div>
      </form>
    );
  }
}

SetPasswordPage.propTypes = {
  token: PropTypes.string.isRequired,
  processState: PropTypes.string,
  setPassword: PropTypes.func.isRequired,
  switchToLogin: PropTypes.func.isRequired,
  componentKey: PropTypes.string.isRequired
};

function validate(values) {
  const errors = {};
  if (!values.password) {
    errors.password = 'Password is required';
  }

  if (values.confirmPassword != values.password) {
    errors.confirmPassword = 'Passwords not match';
  }

  return errors;
}

function mapStateToProps(state, ownProps) {
  return {};
}

function mapActionsToProps(dispatch, ownProps) {
  return {
    setPassword: password => dispatch(authActions.setPassword(ownProps.token, password, ownProps.componentKey)),
    switchToLogin: () => dispatch(applicationActions.selectPage('login'))
  }
}

export default NonAuthPage(connect(mapStateToProps, mapActionsToProps)(reduxForm({
  validate,
  form: 'set-password'
})(SetPasswordPage)));