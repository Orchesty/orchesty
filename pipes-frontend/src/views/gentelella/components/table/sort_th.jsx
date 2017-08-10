import React from 'react'
import Flusanec from 'flusanec';

class SortTh extends Flusanec.Component {
  _initialize() {
    this._onClick = this.onClick.bind(this);
  }

  _useProps(props) {
    const state = this.props.state;
    this._sorted = state && state.key == this.props.name;
    this._desc = this._sorted && typeof state.type == 'string' && state.type.toUpperCase() == 'DESC';
  }

  _finalization() {

  }

  onClick(e){
    e.preventDefault();
    if (typeof this.props.onSortClick == 'function'){
      this.props.onSortClick({
        key: this.props.name,
        type: !this._sorted || this._desc ? 'asc' : 'desc'
      });
    }
  }

  render() {
    const icon = this._sorted ? (this._desc ? <i className="fa fa-sort-amount-desc" /> : <i className="fa fa-sort-amount-asc" />) : false;
    return (
      <th key={this.props.name} onClick={this._onClick} style={{minWidth: '50px'}}>{this.props.children} <div className="pull-right" style={{width: '15px'}}>{icon} </div></th>
    );
  }
}

export default SortTh;