import React from 'react'
import {connect} from 'react-redux';
import {Field, reduxForm} from 'redux-form'

import * as topologyActions from '../../../actions/topologyActions';

import TextInput from '../../elements/form/TextInput';

class TopologyForm extends React.Component {
  constructor(props) {
    super(props);
    this._onSubmit = this.onSubmit.bind(this);
    this._setButton = this.setButton.bind(this);
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
    const {name, descr} = data;
    const {onProcessing} = this.props;
    if (typeof onProcessing == 'function'){
      onProcessing(true);
    }
    this.props.topologyUpdate({name, descr}).then(
      response => {
        const {onSuccess, onProcessing} = this.props;
        if (typeof onProcessing == 'function'){
          onProcessing(false);
        }
        if (response){
          if (typeof onSuccess == 'function'){
            onSuccess(this);
          }
        }
        return response;
      }
    )
  }

  render() {
    return (
      <form className="form-horizontal form-label-left" onSubmit={this.props.handleSubmit(this._onSubmit)}>
        <Field name="_id" component={TextInput} label="Id" readOnly/>
        <Field name="name" component={TextInput} label="Name" />
        <Field name="descr" component={TextInput} label="Description" />
        <button ref={this._setButton} className="hidden" />
      </form>
    );
  }
}

function mapStateToProps(state, ownProps) {
  const {topology} = state;
  return {
    initialValues: topology.elements[ownProps.topologyId]
  };
}

function mapActionsToProps(dispatch, ownProps){
  return {
    topologyUpdate: (data) => dispatch(topologyActions.topologyUpdate(ownProps.topologyId, data))
  }
}

export default connect(mapStateToProps, mapActionsToProps)(reduxForm()(TopologyForm));