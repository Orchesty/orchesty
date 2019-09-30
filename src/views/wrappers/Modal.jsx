import React from 'react';
import PropTypes from 'prop-types';

import StateButton from 'elements/input/StateButton';

export default (WrappedComponent, parameters) => {

  class Modal extends React.Component {
    constructor(props){
      super(props);
      this.closeClick = this.closeClick.bind(this);
      this.close = this.close.bind(this);
      this.makeSubmit = this.makeSubmit.bind(this);
      this.setSubmit = this.setSubmit.bind(this);
      this.keyDown = this.keyDown.bind(this);
      this.setSelf = this.setSelf.bind(this);
      this._submitForm = null;
      this._self = null;
    }

    componentDidMount(){
      this._self && !this._self.contains(document.activeElement) && this._self.focus();
    }

    closeClick(e){
      e.preventDefault();
      this.close();
    }

    close(){
      this.props.onCloseModal(this);
    }

    makeSubmit(){
      this._submitForm();
    }

    setSubmit(submit){
      this._submitForm = submit;
    }

    keyDown(e){
      if (e.keyCode == 27){
        this.close();
      }
    }

    setSelf(self){
      this._self = self;
    }

    render() {
      const {title, subTitle, size, submitCaption, closeCaption, processId, ...passProps} = this.props;
      return (
        <div ref={this.setSelf} className="modal fade manage-user-modal in" tabIndex="0" role="dialog" style={{display: 'block'}} onKeyDown={this.keyDown}>
          <div className={`modal-dialog modal-${size}`}>
            <div className="modal-content">
              <div className="modal-header">
                <button type="button" className="close" onClick={this.closeClick}>
                  <span aria-hidden="true">×</span>
                </button>
                <h4 className="modal-title"><strong>{title}</strong> {subTitle && <small style={{ whiteSpace: 'nowrap' }}>{subTitle}</small>}</h4>
              </div>
              <div className="modal-body">
                <WrappedComponent setSubmit={this.setSubmit} onSuccess={this.close} {...passProps}/>
              </div>
              <div className="modal-footer">
                <button type="button" className="btn btn-default" onClick={this.closeClick}>{closeCaption}</button>
                <StateButton type="button" color="primary" processId={processId} onClick={this.makeSubmit}>{submitCaption}</StateButton>
              </div>
            </div>
          </div>
        </div>
      );
    }
  }

  Modal.defaultProps = Object.assign({
    size: 'md',
    submitCaption: 'Save changes',
    closeCaption: 'Close'
  }, parameters);

  Modal.propTypes = {
    title: PropTypes.string.isRequired,
    subTitle: PropTypes.string,
    onCloseModal: PropTypes.func.isRequired,
    submitCaption: PropTypes.string,
    closeCaption: PropTypes.string,
    processId: PropTypes.string
  };

  Modal.displayName = `Modal(${WrappedComponent.displayName || WrappedComponent.name || 'Component'})`;

  return Modal;
}
