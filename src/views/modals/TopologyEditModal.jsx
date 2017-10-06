import React from 'react'
import PropTypes from 'prop-types';

import TopologyForm from 'components/topology/TopologyForm';
import StateButton from 'elements/input/StateButton';
import processes from "rootApp/enums/processes";

class TopologyEditModal extends React.Component {
  constructor(props) {
    super(props);
    this.closeClick = this.closeClick.bind(this);
    this.close = this.close.bind(this);
    this.makeSubmit = this.makeSubmit.bind(this);
    this.setSubmit = this.setSubmit.bind(this);
    this._submitForm = null;
    this.state = {
      processing: false
    };
  }

  closeClick(e){
    e.preventDefault();
    this.close();
  }

  close(){
    this.props.onCloseModal(this);
  }

  makeSubmit(){
    if (!this.state.processing){
      this._submitForm();
    }
  }
  
  setSubmit(submit){
    this._submitForm = submit;
  }

  render() {
    const {topologyId, addNew, componentKey} = this.props;
    const formKey = 'topology.' + (addNew ? 'new' : topologyId);
    const processId = addNew ? processes.topologyCreate(componentKey) : processes.topologyUpdate(topologyId);
    return (
      <div className="modal fade in" tabIndex="-1" role="dialog" aria-hidden="true" style={{display: 'block', paddingRight: '17px'}}>
        <div className="modal-dialog modal-md">
          <div className="modal-content">
            <div className="modal-header">
              <button type="button" className="close" onClick={this.closeClick}><span aria-hidden="true">×</span></button>
              <h4 className="modal-title" id="myModalLabel">Topology edit</h4>
            </div>
            <div className="modal-body">
              <TopologyForm
                form={formKey}
                setSubmit={this.setSubmit}
                topologyId={topologyId}
                addNew={addNew}
                newProcessId={componentKey}
                onSuccess={this.close}
              />
            </div>
            <div className="modal-footer">
              <button type="button" className="btn btn-default" onClick={this.closeClick}>Close</button>
              <StateButton type="button" color="primary" processId={processId} onClick={this.makeSubmit}>Save changes</StateButton>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

TopologyEditModal.defaultProps = {
  addNew: false
};

TopologyEditModal.propTypes = {
  topologyId: (props, propName, componentName) =>
    typeof props[propName] == 'string' || props.addNew ? null : new Error(`${propName} in ${componentName} must be string or addNew prop must be true`),
  addNew: PropTypes.bool.isRequired,
  onCloseModal: PropTypes.func.isRequired,
  componentKey: PropTypes.string
};

export default TopologyEditModal;