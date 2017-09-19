import React from 'react'
import PropTypes from 'prop-types';

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

  render() {
    const {items, active} = this.props;
    const tabs = items.map((item, index) => (
      <li key={index} className={index == active ? 'active' : null} role="presentation">
        <a href="#" role="tab" onClick={this.tabClick.bind(this, index)}>{item.caption}</a>
      </li>
    ));
    return (
      <ul className="nav nav-tabs bar_tabs" role="tablist">
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
  onChangeTab: PropTypes.func
};

export default TabBar;