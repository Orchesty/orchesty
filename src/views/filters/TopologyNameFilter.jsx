import React from 'react'
import {connect} from 'react-redux';

import FilterCase from 'rootApp/views/wrappers/FilterCase';
import {filterType} from 'rootApp/types';
import FilterElement from 'rootApp/views/wrappers/FilterElement';
import TextInput from 'rootApp/views/elements/input/TextInput';

import './TopologyNameFilter.less';
import * as applicationActions from 'rootApp/actions/applicationActions';

class TopologySelector extends React.Component{
  constructor(props){
    super(props);
    this.keyPressed = this.keyPressed.bind(this);
    this._self = null;
    this.state = {
      selected: null
    }
  }

  componentWillReceiveProps(newProps){
    if (this.state.selected && (!newProps.topologyGroups || newProps.topologyGroups.indexOf(this.state.selected) == -1)){
      this.setState({selected: null});
    }
  }

  componentWillMount(){
    if (this.props.setKeyPressed){
      this.props.setKeyPressed(this.keyPressed);
    }
  }

  componentWillUnmount(){
    if (this.props.setKeyPressed){
      this.props.setKeyPressed(null);
    }
  }

  click(value, e){
    e.preventDefault();
    this.submit(value);
  }

  clickDetail(id, e){
    e.preventDefault();
    e.stopPropagation();
    const topologyGroup = this.props.elements[id];
    this.props.openDetail(topologyGroup.last);
  }

  submit(value){
    const {onChange, name, type} = this.props;
    onChange(name, {type, value: value, property: this.props.property || property});
    this.setState({selected: null});
  }

  select(id){
    this.setState({selected: id});
  }

  moveDown(){
    this.setState(state => {
      const {topologyGroups} = this.props;
      if (topologyGroups && topologyGroups.length > 0){
        let index = 0;
        if (state.selected){
          index = topologyGroups.indexOf(state.selected) + 1;
          if (index >= topologyGroups.length){
            index = 0;
          }
        }
        return {selected: topologyGroups[index]};
      } else {
        return {selected: null}
      }
    });
  }

  moveUp(){
    this.setState(state => {
      const {topologyGroups} = this.props;
      if (topologyGroups && topologyGroups.length > 0){
        let index = -1;
        if (state.selected){
          index = topologyGroups.indexOf(state.selected) - 1;
        }
        if (index < 0){
          index = topologyGroups.length - 1;
        }
        return {selected: topologyGroups[index]};
      } else {
        return {selected: null}
      }
    });
  }

  enter(){
    if (this.state.selected){
      this.submit(this.state.selected);
    }
  }

  keyPressed(e){
    switch (e.keyCode){
      case 40:
        this.moveDown();
        e.preventDefault();
        break;
      case 38:
        this.moveUp();
        e.preventDefault();
        break;
      case 13:
        this.enter();
        e.preventDefault();
        break;
    }
  }

  render(){
    const {topologyGroups} = this.props;
    const {selected} = this.state;
    if (topologyGroups) {
      const items = topologyGroups.length ? topologyGroups.map(id =>
        <li key={id} className={selected && selected == id ? ' active' : ''} onMouseMove={() => this.select(id)} onClick={this.click.bind(this, id)}>
          <a href="#" className="main-link">{id}</a>
          <a href="#" className="direct-link" onClick={this.clickDetail.bind(this, id)}>view detail</a>
        </li>
      ) : null;
      return (
        <div className="selector">
          {items ? <ul>{items}</ul> : <div className="no-match">No match</div>}
        </div>
      );
    } else {
      return null;
    }
  }
}

function mapStateToProps(state, ownProps) {
  const {topologyGroup} = state;
  const {filterItem, focused} = ownProps;
  const value = focused && filterItem && filterItem.type == filterType.SEARCH ? ownProps.filterItem.value : null;
  return {
    elements: topologyGroup.elements,
    topologyGroups: value !== null && value !== '' && value != undefined ? Object.keys(topologyGroup.elements).filter(id => id.indexOf(value) != -1) : null,
    value
  };
}

function mapActionsToProps(dispatch, ownProps){
  return {
    openDetail: (topologyId) => dispatch(applicationActions.selectPage('topology_detail', {topologyId})),
  }
}

const FilterTextInput = FilterElement(TextInput, connect(mapStateToProps, mapActionsToProps)(TopologySelector));

const TopologyNameFilter = FilterCase(FilterTextInput, {
  type: filterType.SEARCH,
  property: 'name',
  label: 'Name',
  icon: 'fa fa-pencil',
  size: 'md',
  subProps: {
    type: filterType.EXACT
  }
});

export default TopologyNameFilter;