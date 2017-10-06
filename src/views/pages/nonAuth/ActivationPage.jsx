import React from 'react'
import {Field, reduxForm} from 'redux-form'
import {connect} from 'react-redux';
import PropTypes from 'prop-types';

import * as authActions from 'actions/authActions';
import * as applicationActions from 'actions/applicationActions';

import TextInput from 'elements/input/TextInput';
import NonAuthPage from 'wrappers/NonAuthPage';
import StateButton from 'elements/input/StateButton';
import processes from "rootApp/enums/processes";

class ActivationPage extends React.Component {
  constructor(props) {
    super(props);
    this.onSubmit = this.onSubmit.bind(this);
    this.loginClick = this.loginClick.bind(this);
  }

  onSubmit(data) {
    this.props.activate(data.token);
  }

  loginClick(e) {
    e.preventDefault();
    this.props.switchToLogin();
  }

  render() {
    const {componentKey} = this.props;
    return (
      <form onSubmit={this.props.handleSubmit(this.onSubmit)}>
        <h1>Activation</h1>
        <div>
          <Field name="token" component={TextInput} label="Token"/>
        </div>
        <div>
          <StateButton type="submit" color="default" processId={processes.authActivate(componentKey)}>Submit</StateButton>
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

ActivationPage.propTypes = {
  token: PropTypes.string,
  activate: PropTypes.func.isRequired,
  switchToLogin: PropTypes.func.isRequired,
  componentKey: PropTypes.string.isRequired
};

function validate(values) {
  const errors = {};
  if (!values.token) {
    errors.token = 'Token is required';
  }

  return errors;
}

function mapStateToProps(state, ownProps) {
  return {
    initialValues: {token: ownProps.token}
  };
}

function mapActionsToProps(dispatch, ownProps) {
  return {
    activate: token => dispatch(authActions.activate(token, ownProps.componentKey)),
    switchToLogin: () => dispatch(applicationActions.selectPage('login'))
  }
}

export default NonAuthPage(connect(mapStateToProps, mapActionsToProps)(reduxForm({
  validate,
  form: 'activation'
})(ActivationPage)));