import React from 'react'
import SeverityFilter from '../../filters/SeverityFilter';
import LogMessageFilter from '../../filters/LogMessageFilter';


class LogListFilter extends React.Component {
  constructor(props) {
    super(props);
    this.changeFilter = this.changeFilter.bind(this);
    this.clearFilter = this.clearFilter.bind(this);
    this.keyDownFilter = this.keyDownFilter.bind(this);
  }

  changeFilter(name, value){
    const {changeFilter, filter} = this.props;
    changeFilter(Object.assign({}, filter, {[name]: value}));
  }

  clearFilter(e){
    const {changeFilter, filter} = this.props;
    e.preventDefault();
    changeFilter(Object.assign({}, filter, {
      severity: null,
      search: null,
    }));
  }

  keyDownFilter(e) {
    if (e.charCode === 13) {
      e.preventDefault();
      e.stopPropagation();
      this.changeFilter('apply', true);
    }
  }

  preventDefault(e){
    e.preventDefault();
  }

  render() {
    const {filter: {severity, search} = {}} = this.props;
    return (
      <form className="filter form-horizontal" onSubmit={this.preventDefault}>
        <SeverityFilter name="severity" filterItem={severity} onChange={this.changeFilter}/>
        <LogMessageFilter name="search" filterItem={search} onChange={this.changeFilter} onKeyPress={this.keyDownFilter} />
        <div className="form-group col-md-2 col-sm-3 col-xs-12">
          <button type="button" className="btn btn-primary" onClick={() => this.changeFilter('apply', true)} style={{marginTop: '27px'}}><i className="fa fa-check" /> Apply</button>
          <button type="button" className="btn btn-default" onClick={this.clearFilter} style={{marginTop: '27px'}}><i className="fa fa-close" /> Clear</button>
        </div>
      </form>
    );
  }
}

export default LogListFilter;