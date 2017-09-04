import React from 'react'

import TopologyForm from '../components/topology/TopologyForm';

class TopologyEditModal extends React.Component {
  constructor(props) {
    super(props);
    this._closeClick = this.closeClick.bind(this);
    this._close = this.close.bind(this);
    this._makeSubmit = this.makeSubmit.bind(this);
    this._submitForm = null;
    this._onProcessing = this.onProcessing.bind(this);
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

  onProcessing(value){
    this.setState({processing: value});
  }

  makeSubmit(){
    if (!this.state.processing){
      this._submitForm();
    }
  }

  render() {
    const {topologyId} = this.props;
    const {processing} = this.state;

    return (
      <div className="modal fade in" tabIndex="-1" role="dialog" aria-hidden="true" style={{display: 'block', paddingRight: '17px'}}>
        <div className="modal-dialog modal-md">
          <div className="modal-content">
            <div className="modal-header">
              <button type="button" className="close" onClick={this._closeClick}><span aria-hidden="true">×</span></button>
              <h4 className="modal-title" id="myModalLabel">Topology edit</h4>
            </div>
            <div className="modal-body">
              <TopologyForm setSubmit={submit => this._submitForm = submit} topologyId={topologyId} onSuccess={this._close} onProcessing={this._onProcessing} />
            </div>
            <div className="modal-footer">
              <button type="button" className="btn btn-default" onClick={this._closeClick}>Close</button>
              <button type="button" className="btn btn-primary" onClick={this._makeSubmit}>{processing ? 'Updating...' : 'Save changes'}</button>
            </div>

          </div>
        </div>
      </div>
    );
  }
}

export default TopologyEditModal;