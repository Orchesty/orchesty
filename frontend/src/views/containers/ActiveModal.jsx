import React from 'react'
import { connect } from 'react-redux';

import * as applicationActions from 'actions/applicationActions';

import TopologyEditModal from 'modals/TopologyEditModal';
import AuthorizationSettingsEditModal from 'modals/AuthorizationSettingsEditModal';
import TopologyRunFormModal from 'modals/TopologyRunFormModal';
import TopologyDeleteDialog from 'modals/dialogs/TopologyDeleteDialog';
import TopologySaveDialog from 'modals/dialogs/TopologySaveDialog';
import TopologyCategoryChangeModal from 'modals/TopologyCategoryChangeModal';
import CategoryEditModal from 'modals/CategoryEditModal';
import HumanTaskApproveFormModal from 'modals/HumanTaskApproveFormModal';
import HumanTaskChangeFormModal from 'modals/HumanTaskChangeFormModal';
import NotificationSettingsFormModal from 'modals/NotificationSettingsFormModal';
import SdkImplChangeFormModal from '../modals/SdkImplChangeFormModal';


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

      case 'topology_save_dialog':
        return <TopologySaveDialog {...modalData} onCloseModal={this._close} componentKey={modal} />;

      case 'category_topology_change':
        return <TopologyCategoryChangeModal {...modalData} onCloseModal={this._close} componentKey={modal} />;

      case 'human_task_approve':
        return <HumanTaskApproveFormModal {...modalData} onCloseModal={this._close} componentKey={modalData.componentKey} />;

      case 'human_task_change':
        return <HumanTaskChangeFormModal {...modalData} onCloseModal={this._close} componentKey={modalData.componentKey} />;

      case 'notification_settings_change':
        return <NotificationSettingsFormModal {...modalData} onCloseModal={this._close} componentKey={modalData.componentKey} />;

      case 'sdk_change':
        return <SdkImplChangeFormModal {...modalData} onCloseModal={this._close} componentKey={modalData.componentKey} />;

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