import React from 'react';
import PropTypes from 'prop-types';
import {connect} from 'react-redux';
import {Field, reduxForm} from 'redux-form';

import * as authorizationActions from 'actions/authorizationActions';

import {FormTextInput} from 'elements/formInputs';
import StateComponent from 'wrappers/StateComponent';
import processes from "rootApp/enums/processes";


class AuthorizationSettingsForm extends React.Component {
  constructor(props) {
    super(props);
    this.onSubmit = this.onSubmit.bind(this);
    this.setButton = this.setButton.bind(this);
    this._button = null;
  }

  componentDidMount() {
    this.props.setSubmit(this.submit.bind(this));
  }

  setButton(button){
    this._button = button;
  }

  submit(){
    this._button.click();
  }

  onSubmit(data){
    const {field1, field2, field3} = data;
    this.props.commitAction({field1, field2, field3}).then(response => {
      const {onSuccess} = this.props;
      if (response && onSuccess){
        onSuccess(this);
      }
    });
  }

  render() {
    return (
      <form className="form-horizontal form-label-left" onSubmit={this.props.handleSubmit(this.onSubmit)}>
        <Field name="field1" component={FormTextInput} label="Field 1" />
        <Field name="field2" component={FormTextInput} label="Field 2" />
        <Field name="field3" component={FormTextInput} label="Field 3" />


          <p>
              <span><strong>Readme:</strong></span><br/>
              {this.props.initialValues.readme}
          </p>

        <button ref={this.setButton} className="hidden" />
      </form>
    );
  }
}

AuthorizationSettingsForm.propTypes = {
  handleSubmit: PropTypes.func.isRequired,
  setSubmit: PropTypes.func.isRequired,
  commitAction: PropTypes.func.isRequired
};

function mapStateToProps(state, ownProps) {
  const {authorization, process} = state;
  const settings = authorization.settings[ownProps.authorizationId];
  return {
    initialValues: settings,
    state: process[processes.authorizationLoadSettings(ownProps.authorizationId)]
  };
}

function mapActionsToProps(dispatch, ownProps){
  return {
    commitAction: (data) => dispatch(authorizationActions.saveSettings(ownProps.authorizationId, data)),
    notLoadedCallback: () => dispatch(authorizationActions.needSettings(ownProps.authorizationId, false))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(StateComponent(reduxForm()(AuthorizationSettingsForm)));