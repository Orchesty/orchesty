import React from 'react'
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import TextInput from '../../elements/input/TextInput';

const createSubscription = name => {
  const innerSubscription = ({ handleSubmit, userId, application, defaultV, enabled, subscribeApplication, unsubscribeApplication }) => (
    <form
      className="col-md-6 col-md-offset-3"
      onSubmit={handleSubmit(data => enabled ? unsubscribeApplication(application, userId, { name, ...data }) : subscribeApplication(application, userId, { name, ...data }))}
    >
      <div className="form-group col-">
        <label>{name} Webhook Topology Name</label>
        <Field component={TextInput} name="topology" readOnly={defaultV || enabled} />
      </div>
      <div className="text-center">
        <button
          type="submit"
          className={`btn btn-${enabled ? 'danger' : 'success'}`}>
          {enabled ? 'Unsubscribe' : 'Subscribe'} {name} Webhook
        </button>
      </div>
      <br />
      <br />
    </form>
  );

  const mapStateToProps = ({ auth: { user: { id: userId } } }, { topology, ...rest }) => ({
    initialValues: { topology },
    userId,
    ...rest
  });

  return connect(mapStateToProps)(reduxForm({
    validate: ({ topology }) => !topology ? { topology: 'Topology name is required' } : {},
    form: `app_store_detail_${name}`,
    enableReinitialize: true,
  })(innerSubscription));
};

export default createSubscription;
