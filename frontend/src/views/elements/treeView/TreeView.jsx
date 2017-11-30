import React from 'react'
import PropTypes from 'prop-types';
import TreeViewItem from 'rootApp/views/elements/treeView/TreeViewItem';

class TreeView extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {root, ...passProps} = this.props;
    return (
      <ul className="tree-view">
        <TreeViewItem item={root} {...passProps}/>
      </ul>
    );
  }
}

TreeViewItem.defaultProps = {
  allOpen: false
};

TreeView.propTypes = {
  root: PropTypes.object,
  allOpen: PropTypes.bool,
  onItemClick: PropTypes.func
};

export default TreeView;