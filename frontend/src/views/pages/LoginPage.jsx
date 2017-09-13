import React from 'react'
import {Field, reduxForm} from 'redux-form'
import { connect } from 'react-redux';

import * as authActions from '../../actions/authActions';

import TextInput from '../elements/input/TextInput';
import PasswordInput from '../elements/input/PasswordInput';

import './LoginPage.less';

class LoginPage extends React.Component {
  constructor(props) {
    super(props);
    this.onSubmit = this.onSubmit.bind(this);
  }

  onSubmit(data){
    this.props.login(data);
  }

  render() {
    return (
      <div className="login" style={{height: '100%'}}>
        <div className="login_wrapper">
          <div className="animate form login_form">
            <section className="login_content">
              <form onSubmit={this.props.handleSubmit(this.onSubmit)}>
                <h1>Login Form</h1>
                <div>
                  <Field name="email" component={TextInput} label="Email" />
                </div>
                <div>
                  <Field name="password" component={PasswordInput} label="Password" />
                </div>
                <div>
                  <button className="btn btn-default submit" type="submit">Log in</button>
                  <a className="reset_pass" href="#">Lost your password?</a>
                </div>

                <div className="clearfix" />

                <div className="separator">
                  <p className="change_link">New to site?
                    <a href="#signup" className="to_register"> Create Account </a>
                  </p>

                  <div className="clearfix" />
                  <br />

                    <div>
                      <h1><i className="fa fa-paw" /> Gentelella Alela!</h1>
                      <p>©2016 All Rights Reserved. Gentelella Alela! is a Bootstrap 3 template. Privacy and Terms</p>
                    </div>
                </div>
              </form>
            </section>
          </div>
        </div>
      </div>
    );
  }
}

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

function mapActionsToProps(dispatch, ownProps){
  return {
    login: data => dispatch(authActions.login(data))
  }
}

export default connect(null, mapActionsToProps)(reduxForm({validate, form: 'login'})(LoginPage));