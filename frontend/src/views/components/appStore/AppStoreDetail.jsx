import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import StateComponent from '../../../views/wrappers/StateComponent';
import { Field, reduxForm } from 'redux-form';
import TextInput from '../../elements/input/TextInput';
import createSubscription from './AppStoreSubscribeForm';
import PasswordInput from '../../elements/input/PasswordInput';
import CheckBoxInput from '../../elements/input/CheckboxInput';
import SelectInput from '../../elements/input/SelectInput';
import NumberInput from '../../elements/input/NumberInput';

class AppStoreDetail extends Component {

  constructor(props) {
    super(props);

    const { getApplication, application, userId } = props;

    getApplication(application, userId);
  }

  componentDidMount() {
    const { name } = this.props;

    document.title = `${name ? `${name} Application` : 'Application Store'} | Pipes Manager`;
  }

  static createRow({ type, key, label, choices, disabled, required, readOnly }) {
    return (
      <div key={key} className="form-group">
        <label>{label}</label>
        {type === 'selectbox' ? (
          <Field
            component={AppStoreDetail.getType(type)}
            name={key}
            options={Object.entries(choices).map(([key, value]) => ({ value: key, label: value }))}
            required={required}
            disabled={disabled}
            readOnly={readOnly}
            showErrors={true}
          />
        ) : (
          <Field
            component={AppStoreDetail.getType(type)}
            name={key}
            required={required}
            disabled={disabled}
            readOnly={readOnly}
            showErrors={true}
          />
        )}
      </div>
    );
  }

  static createSubscription({ name }) {
    return (
      <div key={name}>
        <Field component={TextInput} name="topology" label="Topology" />
      </div>
    )
  }

  static getType(type) {
    switch (type) {
      case 'number':
        return NumberInput;
      case 'password':
        return PasswordInput;
      case 'checkbox':
        return CheckBoxInput;
      case 'selectbox':
        return SelectInput;
      default:
        return TextInput;
    }
  }

  render() {
    const {
      initialValues: { client_id, client_secret },
      name,
      userId,
      expires,
      authorized,
      application,
      description,
      applicationType,
      authorizationType,
      webhookSettings,
      applicationSettings,
      handleSubmit,
      installApplication,
      changeApplication,
      authorizeApplication,
      uninstallApplication,
      subscribeApplication,
      unsubscribeApplication,
    } = this.props;

    if (!applicationType || !authorizationType) {
      return null;
    }

    const subscriptions = webhookSettings && webhookSettings.map(({ name, topology, default: defaultV, enabled }) => ({
      application,
      name,
      topology,
      defaultV,
      enabled,
      subscribeApplication,
      unsubscribeApplication,
      Subscription: createSubscription(name, application, subscribeApplication, unsubscribeApplication)
    }));

    return (
      <div>
        <div className={`text-center ${applicationSettings ? (authorized ? 'text-success' : 'text-danger') : null}`}>
          <h1>{name} Application</h1>
          <h3>{description}</h3>
          <h5>
            Connection type: {applicationType.substr(0, 1).toUpperCase() + applicationType.substr(1).toLowerCase()} connection
          </h5>
          <h5>
            Authorization type: {authorizationType.substr(0, 1).toUpperCase() + authorizationType.substr(1).toLowerCase()} {expires && `(expires ${expires})`}
          </h5>
          <h5>Service name: hbpf.application.{application}</h5>
        </div>
        <br />
        <br />
        {applicationSettings ? (
          <div>
            <div className="text-center">
              <button
                type="button"
                className="btn btn-lg btn-danger"
                onClick={() => uninstallApplication(application, userId)}>
                Uninstall {name} Application
              </button>
              {['oauth', 'oauth2'].includes(authorizationType) && client_id && client_secret && (
                <button
                  type="button"
                  className="btn btn-lg btn-success"
                  onClick={() => authorizeApplication(application, userId, window.location.href)}>
                  Authorize {name} Application
                </button>
              )
              }
            </div>
            <div className="row">
              <br />
              <br />
              <div className="col-md-6 col-md-offset-3">
                <form onSubmit={handleSubmit(data => changeApplication(application, userId, data, applicationSettings))}>
                  {applicationSettings && applicationSettings.map(setting => AppStoreDetail.createRow(setting))}
                  <div className="text-center">
                    <button type="submit" className="btn btn-lg btn-primary">Save Settings</button>
                  </div>
                </form>
                <br />
                <br />
                {authorized && subscriptions && subscriptions.map(
                  ({ Subscription, name, ...rest }) => <Subscription key={name} name={name} {...rest} />
                )}
              </div>
            </div>
          </div>
        ) : (
          <div className="text-center">
            <button
              type="button"
              className="btn btn-lg btn-success"
              onClick={() => installApplication(application, userId)}>
              Install {name} Application
            </button>
          </div>
        )}
      </div>
    );
  }
}

AppStoreDetail.defaultProps = {
  authorized: false,
};

AppStoreDetail.propTypes = {
  authorized: PropTypes.bool.isRequired,
  application: PropTypes.string.isRequired,
  handleSubmit: PropTypes.func.isRequired,
  getApplication: PropTypes.func.isRequired,
  changeApplication: PropTypes.func.isRequired,
  installApplication: PropTypes.func.isRequired,
  uninstallApplication: PropTypes.func.isRequired,
  authorizeApplication: PropTypes.func.isRequired,
  subscribeApplication: PropTypes.func.isRequired,
  unsubscribeApplication: PropTypes.func.isRequired,
  applicationSettings: PropTypes.arrayOf(PropTypes.shape({}).isRequired),
};

const mapStateToProps = ({ appStore: { applications }, auth: { user: { id: userId } } }, { application }) => {
  const innerApplication = applications[application];
  const applicationSettings = innerApplication && innerApplication.applicationSettings || [];

  return {
    userId,
    ...innerApplication,
    initialValues: applicationSettings.reduce((accumulator, { key, value }) => ({ ...accumulator, [key]: value }), {})
  };
};

const validate = (values, { applicationSettings }) => {
  return Object.entries(values).map(([key, value]) => {
    const { type, label, required } = applicationSettings.filter(({ key: innerKey }) => innerKey === key)[0];

    if (!value && required) {
      return { [key]: `${label} is required` }
    }

    switch (type) {
      case 'number':
        return !/\d+/.test(value) ? { [key]: `${label} must be an integer` } : {};
      case 'url':
        return !/https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)/.test(value) ? { [key]: `${label} must be an URL` } : {};
      default:
        return {};
    }
  }).reduce((accumulator, value) => ({ ...accumulator, ...value }), {});
};

const formConfig = {
  validate,
  form: 'app_store_detail',
  enableReinitialize: true,
};

export default connect(mapStateToProps)(reduxForm(formConfig)(StateComponent(AppStoreDetail)));
