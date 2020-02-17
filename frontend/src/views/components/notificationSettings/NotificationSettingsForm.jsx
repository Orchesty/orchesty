import React from 'react'
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form'

import * as notificationSettingsActions from 'rootApp/actions/notificationSettingsActions';
import { FormSelectInput, FormTextAreaInput, FormTextInput } from '../../elements/formInputs';

class NotificationSettingsChangeForm extends React.Component {
  constructor(props) {
    super(props);
    this.onSubmit = this.onSubmit.bind(this);
    this.setButton = this.setButton.bind(this);
    this._button = null;
  }

  componentDidMount() {
    this.props.setSubmit(this.submit.bind(this));
  }

  setButton(button) {
    this._button = button;
  }

  submit() {
    this._button.click();
  }

  onSubmit(data) {
    const { id, onInnerChange, onSuccess } = this.props;

    onInnerChange(id, data).then(
      response => {
        if (response) {
          if (onSuccess) {
            onSuccess(this);
          }
        }
        return response;
      }
    )
  }

  render() {
    const { initialValues: { type, eventOptions } } = this.props;

    const methods = [
      { value: 'GET', label: 'GET' },
      { value: 'POST', label: 'POST' },
      { value: 'PUT', label: 'PUT' },
      { value: 'DELETE', label: 'DELETE' },
    ];

    const encryptions = [
      { value: 'null', label: 'None' },
      { value: 'ssl', label: 'SSL' },
      { value: 'tls', label: 'TLS' },
    ];

    let settings = [];

    switch (type) {
      case "curl":
        settings.push(
          <Field key="method" name="method" component={FormSelectInput} label="Method" options={methods} />,
          <Field key="url" name="url" component={FormTextInput} label="Url" />
        );
        break;
      case "email":
        settings.push(
          <Field key="host" name="host" component={FormTextInput} label="Host" />,
          <Field key="port" name="port" component={FormTextInput} label="Port" />,
          <Field key="username" name="username" component={FormTextInput} label="Username" />,
          <Field key="password" name="password" component={FormTextInput} label="Password" />,
          <Field key="encryption" name="encryption" component={FormSelectInput} label="Encryption" options={encryptions} />,
          <Field key="email" name="email" component={FormTextInput} label="Email (sender)" />,
          <Field key="emails" name="emails" component={FormTextAreaInput} rows={10} label="Emails (one per line)" />,
        );
        break;
      case "rabbit":
        settings.push(
          <Field key="host" name="host" component={FormTextInput} label="Host" />,
          <Field key="port" name="port" component={FormTextInput} label="Port" />,
          <Field key="vhost" name="vhost" component={FormTextInput} label="VHost" />,
          <Field key="user" name="user" component={FormTextInput} label="Username" />,
          <Field key="password" name="password" component={FormTextInput} label="Password" />,
          <Field key="queue" name="queue" component={FormTextInput} label="Queue" />,
        );
        break;

    }

    return (
      <form className="form-horizontal form-label-left" onSubmit={this.props.handleSubmit(this.onSubmit)}>
        <Field name="events" component={FormSelectInput} label="Events" multiple={true} options={eventOptions} />
        {settings}
        <button ref={this.setButton} className="hidden" />
      </form>
    );
  }
}

function validate({ type, method, url, host, port, username, password, encryption, email, emails, vhost, user, queue }) {
  const errors = {};

  switch (type) {
    case 'curl':
      if (!method) {
        errors.method = 'Method must be filled!';
      }

      if (!url) {
        errors.url = 'URL must be filled!';
      }
      break;

    case 'email':
      if (!host) {
        errors.host = 'Host must be filled!';
      }

      if (!port) {
        errors.port = 'Port must be filled!';
      }

      if (!username) {
        errors.username = 'Username must be filled!';
      }

      if (!password) {
        errors.password = 'Password must be filled!';
      }

      if (!encryption) {
        errors.encryption = 'Encryption must be filled!';
      }

      if (!email) {
        errors.email = 'Email (sender) must be filled!';
      }

      if (!emails) {
        errors.emails = 'Emails must be filled!';
      }
      break;

    case 'rabbit':
      if (!host) {
        errors.host = 'Host must be filled!';
      }

      if (!port) {
        errors.port = 'Port must be filled!';
      }

      if (!vhost) {
        errors.vhost = 'Vhost must be filled!';
      }

      if (!user) {
        errors.user = 'User must be filled!';
      }

      if (!password) {
        errors.password = 'Password must be filled!';
      }

      if (!queue) {
        errors.queue = 'Queue must be filled!';
      }
      break;
  }

  return errors;
}

NotificationSettingsChangeForm.propTypes = {
  onInnerChange: PropTypes.func.isRequired,
  onSuccess: PropTypes.func,
  handleSubmit: PropTypes.func.isRequired
};

function mapStateToProps({ notificationSettings: { events: eventOptions } }, { data }) {
  const { type, events, settings, settings: { emails } } = data;

  return {
    initialValues: {
      type,
      events,
      ...settings,
      emails: emails && emails.join('\r\n'),
      eventOptions: Object.entries(eventOptions).map(([value, label]) => ({ value, label })),
    }
  };
}

function mapActionsToProps(dispatch, ownProps) {
  return {
    onInnerChange: (id, data) => dispatch(notificationSettingsActions.notificationSettingsChange(ownProps.componentKey, id, data)),
  }
}

const formConfig = {
  validate,
  form: 'notification_settings_form',
};

export default connect(mapStateToProps, mapActionsToProps)(reduxForm(formConfig)(NotificationSettingsChangeForm));
