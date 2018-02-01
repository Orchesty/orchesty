import React from 'react'
import PropTypes from 'prop-types';
import TopologyTreeViewList from 'rootApp/views/components/topologyTreeView/TopologyTreeViewList';

import './TopologyTreeViewItem.less';

class TopologyTreeViewItem extends React.Component {
  constructor(props) {
    super(props);
    this.categoryClick = this.categoryClick.bind(this);
    this.topologyClick = this.topologyClick.bind(this);
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

  render() {
    const {openTopology, topLevel, item, objectType, toggleCategory} = this.props;
    switch (objectType){
      case 'category':
        return (
          <li className={'topology-tree-view-item' + (topLevel ? ' top-level-item' : ' next-level-item')}>
            <a onClick={this.categoryClick}>
              <i className={item.open ? 'fa fa-folder-open-o' : 'fa fa-folder-o'} />{item.caption} <span className="fa fa-chevron-down" />
            </a>
            {item.open && <TopologyTreeViewList category={item} toggleCategory={toggleCategory} openTopology={openTopology} />}
          </li>
        );
      case 'topology':
        return (
          <li className={'topology-tree-view-item topology' + (topLevel ? ' top-level-item' : ' next-level-item')} >
            <a onClick={this.topologyClick}>
              <i className="fa fa-file-code-o" />{item.name}.v{item.version}
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