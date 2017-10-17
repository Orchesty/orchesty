import React from 'react'

import VisibilityFilter from 'rootApp/views/filters/VisibilityFilter';
import NameFilter from 'rootApp/views/filters/TopologyNameFilter';
import EnabledFilter from 'rootApp/views/filters/EnabledFilter';

class TopologyListFilter extends React.Component {
  constructor(props) {
    super(props);
    this.changeFilter = this.changeFilter.bind(this);
    this.clearFilter = this.clearFilter.bind(this);
  }

  changeFilter(name, value){
    const {changeFilter, filter} = this.props;
    changeFilter(Object.assign({}, filter, {[name]: value}));
  }

  clearFilter(e){
    e.preventDefault();
    this.props.changeFilter({});
  }

  preventDefault(e){
    e.preventDefault();
  }

  render() {
    const {filter: {visibility, name, enabled} = {}} = this.props;
    return (
      <form className="filter form-horizontal" onSubmit={this.preventDefault}>
        <VisibilityFilter name="visibility" filterItem={visibility} onChange={this.changeFilter}/>
        <NameFilter name="name" filterItem={name} onChange={this.changeFilter} />
        <EnabledFilter name="enabled" filterItem={enabled} onChange={this.changeFilter} />
        <div className="form-group col-md-2 col-sm-3 col-xs-12">
          <button type="button" className="btn btn-default" onClick={this.clearFilter} style={{marginTop: '27px'}}><i className="fa fa-close" /> Clear</button>
        </div>
      </form>
    );
  }
}

export default TopologyListFilter;