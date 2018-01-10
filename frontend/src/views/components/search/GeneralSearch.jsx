import React from 'react'
import PropTypes from 'prop-types';
import {connect} from 'react-redux';
import * as generalSearchActions from 'actions/generalSearchActions';
import {stateType} from 'rootApp/types';
import * as searchItems from './searchItems';

import './GeneralSearch.less';
import * as applicationActions from 'rootApp/actions/applicationActions';
import config from 'rootApp/config';
import hotKeyCompare from 'rootApp/utils/hotKeyCompare';
import hotKeyString from 'rootApp/utils/hotKeyString';
import * as categoryActions from 'rootApp/actions/categoryActions';
import * as topologyActions from 'rootApp/actions/topologyActions';

class GeneralSearch extends React.Component {
  constructor(props) {
    super(props);
    this._setSelfInput = null;
    this.changed = this.changed.bind(this);
    this.onFocus = this.onFocus.bind(this);
    this.onHotKey = this.onHotKey.bind(this);
    this.keyPressed = this.keyPressed.bind(this);
    this.setSelf = this.setSelf.bind(this);
    this.setSelfInput = this.setSelfInput.bind(this);
    this.state = {
      focused: false,
      selected: null
    };
  }

  componentWillReceiveProps(newProps){
    const items = newProps.generalSearch.items;
    const selected = this.state.selected;
    if (selected && (!items || items.find(item => item.objectType == selected.objectType && item.id == selected.id) === undefined)){
      this.setState({selected: null});
    }
  }

  componentDidMount(){
    document.addEventListener('focus', this.onFocus, true);
    document.addEventListener('keydown', this.onHotKey, true);
  }

  componentWillUnmount(){
    document.removeEventListener('focus', this.onFocus, true);
    document.removeEventListener('keydown', this.onHotKey, true);
  }

  setSelf(self){
    this._self = self;
  }

  setSelfInput(selfInput){
    this._setSelfInput = selfInput;
  }

  onHotKey(e){
    if (hotKeyCompare(e, config.params.hotKeys.generalSearch)){
      e.preventDefault();
      if (this._setSelfInput){
        this._setSelfInput.focus();
      }
    }
  }

  onFocus(e){
    const newFocused = this._self && e.target && (this._self === e.target || this._self.contains(e.target));
    this.setState({focused: newFocused});
  }

  changed(e){
    this.props.searchAction(e.target.value);
  }

  itemClick(item, e){
    e.preventDefault();
    this.redirect(item);
  }

  redirect(item) {
    const {redirectActions, clearAction} = this.props;
    if (item.objectType != 'topologyGroup') {
      redirectActions[item.objectType](item.id);
    } else {
      redirectActions.topologyGroup(this.props.globalState.topologyGroup.elements[item.id].last);
    }
    config.params.clearGeneralSearch ? clearAction() : this.lostFocus();
  }

  lostFocus(){
    document.body.focus();
  }

  select(item){
    this.setState({selected: item});
  }

  moveDown(){
    this.setState(state => {
      const {generalSearch: {items}} = this.props;
      const selected = state.selected;
      if (items && items.length > 0){
        let index = 0;
        if (selected){
          index = items.reduce((oldIndex, item, index) => item.objectType == selected.objectType && item.id == selected.id ? index + 1 : oldIndex, 0);
          if (index >= items.length){
            index = 0;
          }
        }
        return {selected: items[index]};
      } else {
        return {selected: null}
      }
    });
  }

  moveUp(){
    this.setState(state => {
      const {generalSearch: {items}} = this.props;
      const selected = state.selected;
      if (items && items.length > 0){
        let index = -1;
        if (selected){
          index = items.reduce((oldIndex, item, index) => item.objectType == selected.objectType && item.id == selected.id ? index - 1 : oldIndex, -1);
        }
        if (index < 0){
          index = items.length - 1;
        }
        return {selected: items[index]};
      } else {
        return {selected: null}
      }
    });
  }

  enter(){
    if (this.state.selected){
      this.redirect(this.state.selected);
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

  render() {
    const {generalSearch, globalState} = this.props;
    const {focused, selected} = this.state;
    const items = generalSearch.items.map(item => {
      const ObjectTypeComponent = searchItems[item.objectType];
      return (
        <li
          key={`${item.objectType}#${item.id}`}
          className={'search-item' + (selected && selected.id == item.id && selected.objectType == item.objectType ? ' active' : '')}
          onClick={this.itemClick.bind(this, item)}
          onMouseMove={() => this.select(item)}
        >
          <ObjectTypeComponent item={item} globalState={globalState}/>
        </li>
      );
    });
    return (
      <div ref={this.setSelf} className="form-group top_search" tabIndex="0" onKeyDown={this.keyPressed}>
        <div className="input-group">
          <input
            ref={this.setSelfInput}
            type="text" className="form-control"
            placeholder={hotKeyString(config.params.hotKeys.generalSearch)}
            onChange={this.changed}
            value={generalSearch.search}
          />
          <span className="input-group-btn">
            <button className="btn btn-default" type="button">Go!</button>
          </span>
        </div>
        {
          focused && generalSearch.state == stateType.SUCCESS &&
          <div className="selector">
            {items && items.length > 0 ? <ul>{items}</ul> : <div className="no-match">No match</div>}
          </div>
        }
      </div>
    );
  }
}

GeneralSearch.propTypes = {
  searchAction: PropTypes.func.isRequired,
  clearAction: PropTypes.func.isRequired,
  generalSearch: PropTypes.object.isRequired,
  redirectActions: PropTypes.object.isRequired
};

function mapStateToProps(state, ownProps){
  const {generalSearch} = state;
  return {
    generalSearch,
    globalState: state
  };
}

function mapActionsToProps(dispatch, ownProps){
  return {
    searchAction: searchStr => dispatch(generalSearchActions.search(searchStr)),
    clearAction: () => dispatch(generalSearchActions.clear()),
    redirectActions: {
      topologyGroup: id => dispatch(applicationActions.selectPage('topology_detail', {topologyId: id})),
      category: id => {
        dispatch(applicationActions.selectPage('topology_list', {categoryId: id}));
        dispatch(categoryActions.treeItemClick('topology_list', id, () => {
          dispatch(topologyActions.refreshList('topology_list'))
        }));
      }
    }
  };
}

export default connect(mapStateToProps, mapActionsToProps)(GeneralSearch);