import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';
import {Field, reduxForm} from 'redux-form'

import * as categoryActions from 'actions/categoryActions';

import {FormTextInput} from 'elements/formInputs';

class CategoryForm extends React.Component {
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
    const sendData = {name: data.name};
    this.props.commitAction(sendData).then(
      response => {
        const {onSuccess} = this.props;
        if (response){
          if (onSuccess){
            onSuccess(this);
          }
        }
        return response;
      }
    )
  }

  render() {
    const {addNew, initialValues} = this.props;

    return (
      <form className="form-horizontal form-label-left" onSubmit={this.props.handleSubmit(this.onSubmit)}>
        {!addNew && <Field name="_id" component={FormTextInput} label="Id" readOnly/>}
        <Field name="name" component={FormTextInput} label="Name" autoFocus/>
        <button ref={this.setButton} className="hidden" />
      </form>
    );
  }
}

CategoryForm.propTypes = {
  addNew: PropTypes.bool,
  handleSubmit: PropTypes.func.isRequired,
  setSubmit: PropTypes.func.isRequired,
  onSuccess: PropTypes.func,
  commitAction: PropTypes.func.isRequired
};

function validate(values){
  const errors = {};
  if (!values.name) {
    errors.name = 'Name is required';
  }

  return errors;
}

function mapStateToProps(state, ownProps) {
  const {category} = state;
  return {
    initialValues: ownProps.addNew ? {enabled: false} : category.elements[ownProps.categoryId]
  };
}

function mapActionsToProps(dispatch, ownProps){
  return {
    commitAction: (data) => dispatch(
      ownProps.addNew ? categoryActions.createCategory(Object.assign(data, {parent: ownProps.parentId ? ownProps.parentId : null}), ownProps.newProcessId) : categoryActions.updateCategory(ownProps.categoryId, data)
    )
  }
}

export default connect(mapStateToProps, mapActionsToProps)(reduxForm({validate})(CategoryForm));