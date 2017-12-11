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
    this.startEdit = this.startEdit.bind(this);
    this.setControlFunctions = this.setControlFunctions.bind(this);
    this.editControlFunctions = {};
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
    const {createAction, item, editableSwitchEdit, componentKey} = this.props;
    e.preventDefault();
    createAction(item.id).then(res => {
      editableSwitchEdit(`${componentKey}.${res._id}`);
      return res;
    });
  }

  makeDelete(e){
    const {deleteAction, item} = this.props;
    e.preventDefault();
    deleteAction(item.id);
  }

  startEdit(e){
    e.preventDefault();
    if (this.editControlFunctions.switchEdit){
      this.editControlFunctions.switchEdit();
    }
  }

  setControlFunctions(functions){
    this.editControlFunctions = functions;
  }

  render() {
    const {item, ...passProps} = this.props;
    const {allOpen, componentKey} = this.props;
    const openable = item.children && item.children.length;
    const open = (allOpen || item.open) && openable;
    const editable = item.id !== null;

    const children = open && item.children.map(item => <TreeViewItem key={item.id} item={item} {...passProps}/>);
    return (
      <li className={(item.selected ? 'selected' : '') + (openable ? (open ? ' open' : ' close') : '')}>
        <div className="tree-view-item">
          {open ? <span className="caret" /> : <span className="empty" />}
          <a className="item-caption" href="#" onClick={this.itemClick}>
            <TextEditable
              commitAction={this.edit}
              componentKey={`${componentKey}.${item.id}`}
              value={item.caption}
              setControlFunction={this.setControlFunctions}
            />
          </a>
          <span className="quick-buttons">
            {editable && <a href="#" onClick={this.startEdit}><i className="fa fa-pencil" /></a>}
            <a href="#" onClick={this.create}><i className="fa fa-plus-circle" /></a>
            {editable && <a href="#" onClick={this.makeDelete}><i className="fa fa-times" /></a>}
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
  editableSwitchEdit: PropTypes.func.isRequired
};

export default TreeViewItem;