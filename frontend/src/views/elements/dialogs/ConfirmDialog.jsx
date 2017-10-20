import React from 'react'
import PropTypes from 'prop-types';

class ConfirmDialog extends React.Component {
  constructor(props) {
    super(props);
    this.closeClick = this.closeClick.bind(this);
    this.close = this.close.bind(this);
    this.keyDown = this.keyDown.bind(this);
    this.setSelf = this.setSelf.bind(this);
    this.makeConfirm = this.makeConfirm.bind(this);
  }

  componentDidMount(){
    this._self && this._self.focus();
  }

  closeClick(e){
    e.preventDefault();
    this.close();
  }

  close(){
    this.props.onCloseModal(this);
  }

  keyDown(e){
    if (e.keyCode == 27){
      this.close();
    }
  }

  setSelf(self){
    this._self = self;
  }

  makeConfirm(e){
    e.preventDefault();
    this.close();
    this.props.confirmAction();
  }

  render() {
    const {message} = this.props;
    return (
      <div ref={this.setSelf} className="modal fade in" tabIndex="0" role="dialog" style={{display: 'block'}} onKeyDown={this.keyDown}>
        <div className={`modal-dialog modal-md`}>
          <div className="modal-content">
            <div className="modal-header">
              <button type="button" className="close" onClick={this.closeClick}>
                <span aria-hidden="true">×</span>
              </button>
              <h4 className="modal-title"><strong>Confirm</strong></h4>
            </div>
            <div className="modal-body">
              {message}
            </div>
            <div className="modal-footer">
              <button type="button" className="btn btn-default" onClick={this.closeClick}>Cancel</button>
              <button type="button" className="btn btn-primary" onClick={this.makeConfirm}>Confirm</button>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

ConfirmDialog.propTypes = {
  message: PropTypes.string.isRequired,
  confirmAction: PropTypes.func.isRequired,
  onCloseModal: PropTypes.func.isRequired
};


export default ConfirmDialog;