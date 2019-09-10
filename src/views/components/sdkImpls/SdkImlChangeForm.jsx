import React from 'react'
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form'
import { FormTextInput } from '../../elements/formInputs';
import * as sdkImplsActions from '../../../actions/sdkImplsActions';

class SdkImlChangeForm extends React.Component {
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
    const { id, onCreate, onUpdate, onSuccess } = this.props;

    if (id) {
      onUpdate(id, data).then(() => onSuccess(this));
    } else {
      onCreate(data).then(() => onSuccess(this));
    }
  }

  render() {
    return (
      <form className="form-horizontal form-label-left" onSubmit={this.props.handleSubmit(this.onSubmit)}>
        <Field name="key" component={FormTextInput} label="Key" autoFocus={true} />
        <Field name="value" component={FormTextInput} label="Value" />
        <button ref={this.setButton} className="hidden" />
      </form>
    );
  }
}

function validate(values, { keys, cKey }) {
  const errors = {};

  if (!values.key) {
    errors.key = 'Key must be filled!';
  }

  if (keys.includes(values.key) && values.key !== cKey) {
    errors.key = 'Key must be unique!';
  }

  if (!values.value) {
    errors.value = 'Value must be filled!';
  }

  return errors;
}

SdkImlChangeForm.intialProps = {
  id: undefined,
};

SdkImlChangeForm.propTypes = {
  id: PropTypes.string,
  onCreate: PropTypes.func.isRequired,
  onUpdate: PropTypes.func.isRequired,
  onSuccess: PropTypes.func.isRequired,
  handleSubmit: PropTypes.func.isRequired
};

const mapStateToProps = ({ sdkImpls: { elements } }, { data: { _id, key, value } = {} }) => ({
  id: _id,
  initialValues: { key, value },
  cKey: key,
  keys: Object.entries(elements).map(([_, { key }]) => key),
});

const mapActionsToProps = (dispatch, { componentKey }) => ({
  onCreate: data => dispatch(sdkImplsActions.create(data, componentKey)),
  onUpdate: (id, data) => dispatch(sdkImplsActions.update(id, data, componentKey)),
});

export default connect(mapStateToProps, mapActionsToProps)(reduxForm({ validate })(SdkImlChangeForm));
