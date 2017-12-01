import React from 'react'
import PropTypes from 'prop-types';
import TreeViewItem from 'rootApp/views/elements/treeView/TreeViewItem';

import './TreeView.less';

class TreeView extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {root, ...passProps} = this.props;
    return (
      <div className="tree-view">
        <ul>
          <TreeViewItem item={root} {...passProps}/>
        </ul>
      </div>
    );
  }
}

TreeViewItem.defaultProps = {
  allOpen: false
};

TreeView.propTypes = {
  root: PropTypes.object,
  allOpen: PropTypes.bool,
  onItemClick: PropTypes.func,
  componentKey: PropTypes.string,
  editAction: PropTypes.func.isRequired
};

export default TreeView;