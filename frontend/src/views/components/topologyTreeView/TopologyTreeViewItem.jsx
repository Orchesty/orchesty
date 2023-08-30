import React from 'react'
import PropTypes from 'prop-types';
import TopologyTreeViewList from 'rootApp/views/components/topologyTreeView/TopologyTreeViewList';

import './TopologyTreeViewItem.less';

class TopologyTreeViewItem extends React.Component {
  constructor(props) {
    super(props);
    this.categoryClick = this.categoryClick.bind(this);
    this.topologyClick = this.topologyClick.bind(this);
    this.contextMenuClick = this.contextMenuClick.bind(this);
  }

  categoryClick(e){
    const {toggleCategory, item} = this.props;
    e.preventDefault();
    toggleCategory(item.id);
  }

  topologyClick(e){
    const {openTopology, item} = this.props;
    e.preventDefault();
    openTopology(item._id);
  }

  contextMenuClick(e){
    const {openContextMenu, objectType, item} = this.props;
    e.preventDefault();
    openContextMenu(item.id || item._id, objectType, e.clientX, e.clientY);
  }

  render() {
    const {openTopology, topLevel, item, objectType, toggleCategory, openContextMenu} = this.props;
    switch (objectType){
      case 'category':
        return (
          <li className={'topology-tree-view-item' + (topLevel ? ' top-level-item' : ' next-level-item')}>
            <a onClick={this.categoryClick} onContextMenu={this.contextMenuClick}>
              <i className={item.open ? 'fa fa-folder-open-o' : 'fa fa-folder-o'} />{item.caption} <span className="fa fa-chevron-down" />
            </a>
            {item.open && <TopologyTreeViewList category={item} toggleCategory={toggleCategory} openTopology={openTopology} openContextMenu={openContextMenu} />}
          </li>
        );
      case 'topology':
        return (
          <li className={'topology-tree-view-item topology' + (topLevel ? ' top-level-item' : ' next-level-item')} >
            <a onClick={this.topologyClick} onContextMenu={this.contextMenuClick} className={`${item.enabled ? 'enabled' : 'disabled'} ${item.type}`}>
              <i className={item.type === 'cron' ? 'fa fa-clock-o' : 'fa fa-file-code-o'} />{item.name}.v{item.version}
            </a>
          </li>
        );
      default:
        return undefined;
    }
  }
}

TopologyTreeViewItem.defaultProps = {
  topLevel: false
};

TopologyTreeViewItem.propTypes = {
  item: PropTypes.object.isRequired,
  topLevel: PropTypes.bool.isRequired,
  toggleCategory: PropTypes.func,
  openTopology: PropTypes.func.isRequired
};

export default TopologyTreeViewItem;
