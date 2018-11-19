import React from 'react'
import CategoryTreeView from 'components/category/CategoryTreeView';
import AllTopologyListTable from 'rootApp/views/components/topology/AllTopologyListTable';
import {connect} from 'react-redux';
import * as topologyActions from 'rootApp/actions/topologyActions';
import {filterType} from 'rootApp/types';
import * as applicationActions from 'rootApp/actions/applicationActions';

import './TopologyCategoryList.less';
import ActionButtonPanel from 'rootApp/views/elements/actions/ActionButtonPanel';

class TopologyCategoryList extends React.Component {
  constructor(props) {
    super(props);
    this.categorySelected = this.categorySelected.bind(this);
  }

  getPageActions(){
    const {setActions, openNewTopology} = this.props;
    const pageActions = [];
    if (openNewTopology) {
      pageActions.push({
        caption: 'Create topology',
        action: () => openNewTopology(this.props.categoryId)
      });
    }
    return pageActions;
  }

  categorySelected(categoryId){
    this.props.refreshList();
  }

  render() {
    const {componentKey} = this.props;
    const topologyFilter = {
      category: {
        type: filterType.EXACT_NULL,
        storedValue: ['category', 'trees', componentKey, 'selectedId'],
        property: 'category'
      }
    };
    return (
      <div className="topology-category-list">
        <CategoryTreeView componentKey={componentKey} onSelect={this.categorySelected}/>
        <div className="topology-sub-page">
          <div className="sub-page-title">
            <div className="pull-right">
              <ActionButtonPanel items={this.getPageActions()} size="md" right={true}/>
            </div>
          </div>
          <div className="sub-page-content">
            <AllTopologyListTable componentKey={componentKey} filter={topologyFilter}/>
          </div>
        </div>
      </div>
    );
  }
}

TopologyCategoryList.propTypes = {};

function mapStateToProps(state, ownProps) {
  const {category} = state;
  const tree = category.trees[ownProps.componentKey];
  return {
    categoryId: tree ? tree.selectedId : null
  }
}

function mapActionsToProps(dispatch, ownProps){
  return {
    refreshList: () => dispatch(topologyActions.refreshList(ownProps.componentKey)),
    openNewTopology: categoryId => dispatch(applicationActions.openModal('topology_edit', {addNew: true, categoryId})),
  }
}

export default connect(mapStateToProps, mapActionsToProps)(TopologyCategoryList);