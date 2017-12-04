import React from 'react'
import PropTypes from 'prop-types';
import {TextEditable} from 'elements/editables';

class TreeViewItem extends React.Component {
  constructor(props) {
    super(props);
    this.itemClick = this.itemClick.bind(this);
    this.edit = this.edit.bind(this);
    this.create = this.create.bind(this);
    this.makeDelete = this.makeDelete.bind(this);
  }

  itemClick(e){
    const {onItemClick, item} = this.props;
     e.preventDefault();
     if (onItemClick){
       onItemClick(item.id);
     }
  }

  edit(value) {
    const {editAction, item} = this.props;
    editAction(item.id, value);
  }

  create(e){
    const {createAction, item} = this.props;
    e.preventDefault();
    createAction(item.id);
  }

  makeDelete(e){
    const {deleteAction, item} = this.props;
    e.preventDefault();
    deleteAction(item.id);
  }

  render() {
    const {item, ...passProps} = this.props;
    const {allOpen, componentKey} = this.props;
    const openable = item.children && item.children.length;
    const open = (allOpen || item.open) && openable;

    const children = open && item.children.map(item => <TreeViewItem key={item.id} item={item} {...passProps}/>);
    return (
      <li className={(item.selected ? 'selected' : '') + (openable ? (open ? ' open' : ' close') : '')}>
        <div className="tree-view-item">
          <a className="item-caption" href="#" onClick={this.itemClick}><TextEditable commitAction={this.edit} componentKey={`${componentKey}.${item.id}`} value={item.caption} /></a>
          <span className="quick-buttons">
            <a href="#" onClick={this.create}><i className="fa fa-plus-circle" /></a> <a href="#" onClick={this.makeDelete}><i className="fa fa-times" /></a>
          </span>
        </div>
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
  onItemClick: PropTypes.func,
  editAction: PropTypes.func.isRequired,
  createAction: PropTypes.func.isRequired,
  deleteAction: PropTypes.func.isRequired,
};

export default TreeViewItem;