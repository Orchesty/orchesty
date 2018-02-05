import React from 'react'
import { connect } from 'react-redux';

import * as applicationActions from 'actions/applicationActions';

import TopologyEditModal from 'modals/TopologyEditModal';
import AuthorizationSettingsEditModal from 'modals/AuthorizationSettingsEditModal';
import TopologyRunFormModal from 'modals/TopologyRunFormModal';
import TopologyDeleteDialog from 'modals/dialogs/TopologyDeleteDialog';
import TopologyCategoryChangeModal from 'modals/TopologyCategoryChangeModal';
import CategoryEditModal from 'modals/CategoryEditModal';


class ActiveModal extends React.Component {
  constructor(props) {
    super(props);
    this._close = this.close.bind(this);
  }
  
  close(){
    this.props.closeModal();
  }
  
  render() {
    const {modal, modalData} = this.props;
    switch (modal){
      case 'topology_edit':
        return <TopologyEditModal {...modalData} onCloseModal={this._close} componentKey={modal}/>;

      case 'category_edit':
        return <CategoryEditModal {...modalData} onCloseModal={this._close} componentKey={modal}/>;

      case 'authorization_settings_edit':
        return <AuthorizationSettingsEditModal {...modalData} onCloseModal={this._close} componentKey={modal} />;

      case 'node_run':
        return <TopologyRunFormModal {...modalData} onCloseModal={this._close} componentKey={modal} />;

      case 'topology_delete_dialog':
        return <TopologyDeleteDialog {...modalData} onCloseModal={this._close} componentKey={modal} />;

      case 'category_topology_change':
        return <TopologyCategoryChangeModal {...modalData} onCloseModal={this._close} componentKey={modal} />;
      default:
        return null;
    }
  }
}

function mapStateToProps(state){
  const {modal, modalData} = state.application;

  return {
    modal,
    modalData
  }
}

function mapActionsToProps(dispatch){
  return {
    closeModal: (id, data) => dispatch(applicationActions.closeModal())
  }
}

export default connect(mapStateToProps, mapActionsToProps)(ActiveModal);