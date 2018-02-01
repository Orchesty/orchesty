import React from 'react'
import PropTypes from 'prop-types';

import './TabBar.less';

class TabBar extends React.Component {
  constructor(props) {
    super(props);
  }

  tabClick(index, e){
    const {active, items, onChangeTab} = this.props;
    e.preventDefault();
    if (index != active && onChangeTab){
      onChangeTab(items[index], index);
    }
  }

  closeClick(index, e){
    const {onClose} = this.props;
    e.preventDefault();
    onClose(index);
  }

  render() {
    const {items, active, onClose} = this.props;
    const tabs = items.map((item, index) => (
      <li key={index} className={index == active ? 'active' : null} role="presentation">
        <a href="#" role="tab" onClick={this.tabClick.bind(this, index)}>
          {item.caption}
          {onClose && <span className="tab-close" onClick={this.closeClick.bind(this, index)}><i className="fa fa-close" /></span>}
        </a>
      </li>
    ));
    return (
      <ul className={'nav nav-tabs bar_tabs tab-bar' + (onClose ? ' tab-closeable' : '')} role="tablist">
        {tabs}
      </ul>
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