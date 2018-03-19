import React from 'react'
import PropTypes from 'prop-types';

import './TabBar.less';
import {menuItemType} from 'rootApp/types';
import ActionButton from 'rootApp/views/elements/actions/ActionButton';

class TabBar extends React.Component {
  constructor(props) {
    super(props);
    this.setSelf = this.setSelf.bind(this);
    this._self = null;
    this._intervalId = null;
    this.state = {
      maxIndex: null
    }
  }

  componentDidMount(){
    this._intervalId = setInterval(() => {
      if (this._self){
        const maxBottom = this._self.getClientRects()[0].bottom + 1;
        const children = this._self.childNodes;
        let maxIndex = null;
        for (let i = 0, solved = false; i < children.length && !solved; i++){
          const child = children[i];
          if (child.getClientRects()[0].bottom > maxBottom){
            solved = true;
            maxIndex = i;
          }
        }
        this.setState({maxIndex});
      }
    }, 333);
  }

  componentWillUnmount(){
    if (this._intervalId){
      clearInterval(this._intervalId);
    }
  }

  setSelf(self){
    this._self = self;
  }

  tabClick(index, e){
    const {active, items, onChangeTab} = this.props;
    e && e.preventDefault();
    if (index != active && onChangeTab){
      onChangeTab(items[index], index);
    }
  }

  closeClick(index, e){
    const {onClose, items} = this.props;
    e.preventDefault();
    e.stopPropagation();
    let newIndex = index - 1;
    if (newIndex < 0){
      newIndex = index + 1;
      if (newIndex > items.length){
        newIndex = null;
      }
    }
    onClose(items[index], newIndex !== null ? items[newIndex] : null);
  }

  render() {
    const {items, active, onClose} = this.props;
    const {maxIndex} = this.state;
    const hiddenItems = [];
    const tabs = items.map((item, index) => {
      if (maxIndex !== null && index >= maxIndex){
        hiddenItems.push({
          type: menuItemType.ACTION,
          caption: item.caption,
          action: this.tabClick.bind(this, index)
        });
      }
      return (
        <div key={index} className={'tab' + (index == active ? ' active' : '') + (maxIndex !== null && index >= maxIndex ? ' collapsed' : '')}>
          <a href="#" onClick={this.tabClick.bind(this, index)}>
            {item.caption}
            {onClose &&
            <span className="tab-close" onClick={this.closeClick.bind(this, index)}><i className="fa fa-close"/></span>}
          </a>
        </div>
      );
    });

    const actions = {
      type: menuItemType.SUB_MENU,
      caption: <i className="fa fa-bars" />,
      items: hiddenItems
    };

    return (
      <div className="tab-container">
        <div ref={this.setSelf} className={'tabs' + (onClose ? ' tab-closeable' : '')}>
          {tabs}
        </div>
        {maxIndex !== null && <div className="tab-menu"><ActionButton size={'md'} color={'default'} item={actions} right /></div>}
      </div>
    );
  }
}

TabBar.propTypes = {
  items: PropTypes.arrayOf(PropTypes.shape({
    caption: PropTypes.string.isRequired
  })).isRequired,
  active: PropTypes.number,
  onChangeTab: PropTypes.func,
  onClose: PropTypes.func
};

export default TabBar;