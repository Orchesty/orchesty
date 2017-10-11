import React from 'react'
import {Field, reduxForm} from 'redux-form';

import {FilterSelect} from 'elements/filterInputs';

const visibilityOptions = [
  {value: '', label: 'All'},
  {value: 'public', label: 'Public'},
  {value: 'draft', label: 'Draft'}
];

class TopologyListFilter extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    return (
      <form className="form-horizontal">
        <Field name="visibility" component={FilterSelect} label="Visibility" icon="fa fa-eye" options={visibilityOptions} />
      </form>
    );
  }
}

export default reduxForm({form: 'todo-change-it'})(TopologyListFilter);