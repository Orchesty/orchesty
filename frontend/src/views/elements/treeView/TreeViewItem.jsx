import React from 'react'
import PropTypes from 'prop-types';

class TreeViewItem extends React.Component {
  constructor(props) {
    super(props);
    this.itemClick = this.itemClick.bind(this);
  }

  itemClick(e){
    const {onItemClick, item} = this.props;
     e.preventDefault();
     if (onItemClick){
       onItemClick(item.id);
     }
  }

  render() {
    const {item, ...passProps} = this.props;
    const {allOpen} = this.props;
    const openable = item.children && item.children.length;
    const open = (allOpen || item.open) && openable;
    const children = open && item.children.map(item => <TreeViewItem key={item.id} item={item} {...passProps}/>);
    return (
      <li className={item.selected ? 'selected' : ''}>
        <a href="#" onClick={this.itemClick}>{item.caption}</a>
        {open && <ul>{children}</ul>}
      </li>
    );
  }
}

TreeViewItem.defaultProps = {
  allOpen: false
};

TreeViewItem.propTypes = {
  allOpen: PropTypes.bool,
  item: PropTypes.shape({
    id: PropTypes.oneOfType([PropTypes.number, PropTypes.string]),
    selected: PropTypes.bool,
    open: PropTypes.bool,
    caption: PropTypes.string.isRequired,
    children: PropTypes.arrayOf(PropTypes.object)
  }).isRequired,
  onItemClick: PropTypes.func
};

export default TreeViewItem;