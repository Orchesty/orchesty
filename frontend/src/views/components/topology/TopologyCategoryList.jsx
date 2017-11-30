import React from 'react'
import PropTypes from 'prop-types';
import CategoryTreeView from 'components/category/CategoryTreeView';
import AllTopologyListTable from 'rootApp/views/components/topology/AllTopologyListTable';
import {connect} from 'react-redux';
import * as topologyActions from 'rootApp/actions/topologyActions';
import {filterType} from 'rootApp/types';
import * as applicationActions from 'rootApp/actions/applicationActions';

class TopologyCategoryList extends React.Component {
  constructor(props) {
    super(props);
    this.categorySelected = this.categorySelected.bind(this);
  }

  componentWillMount(){
    this._sendActions();
  }

  _sendActions(){
    const {setActions, openNewTopology} = this.props;
    const pageActions = [];
    if (openNewTopology) {
      pageActions.push({
        caption: 'Create topology',
        action: () => openNewTopology(this.props.categoryId)
      });
    }
    setActions(pageActions);
  }

  categorySelected(categoryId){
    this.props.refreshList();
  }

  render() {
    const {pageKey} = this.props;
    const topologyFilter = {
      category: {
        type: filterType.EXACT_NULL,
        storedValue: ['category', 'trees', pageKey, 'selectedId'],
        property: 'category'
      }
    };
    return (
      <div className="topology-category-list">
        <CategoryTreeView componentKey={pageKey} onSelect={this.categorySelected}/>
        <AllTopologyListTable componentKey={pageKey} filter={topologyFilter}/>
      </div>
    );
  }
}

TopologyCategoryList.propTypes = {};

function mapStateToProps(state, ownProps) {
  const {category} = state;
  const tree = category.trees[ownProps.pageKey];
  return {
    categoryId: tree ? tree.selectedId : null
  }
}

function mapActionsToProps(dispatch, ownProps){
  return {
    refreshList: () => dispatch(topologyActions.refreshList(ownProps.pageKey)),
    openNewTopology: categoryId => dispatch(applicationActions.openModal('topology_edit', {addNew: true, categoryId})),
  }
}

export default connect(mapStateToProps, mapActionsToProps)(TopologyCategoryList);