import React from 'react'
import {Field, reduxForm} from 'redux-form';

import {FilterSelect} from 'elements/filterInputs';
import VisibilityFilter from 'rootApp/views/filters/VisibilityFilter';

class TopologyListFilter extends React.Component {
  constructor(props) {
    super(props);
    this.changeFilter = this.changeFilter.bind(this);
  }

  changeFilter(name, value){
    const {changeFilter, filter} = this.props;
    changeFilter(Object.assign({}, filter, {[name]: value}));
  }

  render() {
    const {filter: {visibility} = {}} = this.props;
    return (
      <form className="form-horizontal">
        <VisibilityFilter name="visibility" value={visibility} onChange={this.changeFilter}/>
      </form>
    );
  }
}

export default reduxForm({form: 'todo-change-it'})(TopologyListFilter);