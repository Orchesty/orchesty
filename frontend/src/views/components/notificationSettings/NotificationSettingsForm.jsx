import React from 'react'
import PropTypes from 'prop-types';
import {Field, FieldArray, reduxForm} from 'redux-form';
import StateButton from 'rootApp/views/elements/input/StateButton';
import {isEmail} from 'rootApp/utils/validations';
import CheckboxInput from 'elements/input/CheckboxInput';
import {BasicFormTagsInput} from 'rootApp/views/elements/basicFormInputs';

function renderLabel(props){
  return <span>{props.input.value}</span>;
}

function renderEventSettings({fields}){
  const items = fields.map((member, index) =>
    <tr key={index} className="event-setting-row">
      <td><Field name={`${member}.name`} component={renderLabel}/></td>
      <td><Field name={`${member}.outputs.email`} component={CheckboxInput}/></td>
      <td><Field name={`${member}.outputs.client`} component={CheckboxInput}/></td>
    </tr>
  );
  return (
    <table className="table">
      <thead>
        <tr>
          <th>Event</th>
          <th>Email</th>
          <th>Client</th>
        </tr>
      </thead>
      <tbody>
      {items}
      </tbody>
    </table>
  );
}

class NotificationSettingsForm extends React.Component {
  constructor(props) {
    super(props);
    this.onSubmit = this.onSubmit.bind(this);
  }


  onSubmit(data){
    data.event_settings.forEach(eventSetting => {
      eventSetting.outputs.email = Boolean(eventSetting.outputs.email);
      eventSetting.outputs.client = Boolean(eventSetting.outputs.client);
    });
    this.props.commitAction(data);
  }

  render() {
    const {processId} = this.props;
    return (
      <form onSubmit={this.props.handleSubmit(this.onSubmit)}>
        <Field name="subscribers" component={BasicFormTagsInput} label="Subscribers" placeholder="Add subscriber" onlyUnique/>
        <FieldArray name="event_settings" component={renderEventSettings} />
        <StateButton type="submit" color="primary" processId={processId} >Save</StateButton>
      </form>
    );
  }
}

function validate(values){
  const errors = {};
  values.subscribers.forEach(email => {
    if (!isEmail(email)){
      errors.subscribers = 'Invalid email address.';
    }
  });
  return errors;
}

NotificationSettingsForm.propTypes = {
  commitAction: PropTypes.func.isRequired,
  processId: PropTypes.string.isRequired
};

export default reduxForm({validate})(NotificationSettingsForm);