import React from 'react'
import ReactDOM from 'react-dom';
import Flusanec from 'flusanec';

let open_file_dialog_less = require('./open_file_dialog.less');

class OpenFileDialog extends Flusanec.Component {
  _initialize() {
    this._onOpenDialog = this.onOpenDialog.bind(this);
  }

  _useProps(props) {
    this.service = props.openFileDialogService;
  }

  _finalization() {
    this.service = null;
  }

  set service(value:OpenFileDialogService){
    if (this._service != value){
      this._service && this._service.removeOpenDialogListener(this._onOpenDialog);
      this._service = value;
      this._service && this._service.addOpenDialogListener(this._onOpenDialog);
    }
  }

  onOpenDialog(_, callback){
    const element = ReactDOM.findDOMNode(this);
    const onSelect = e => {
      callback(e.target.files[0]);
      element.removeEventListener('change', onSelect);
    };
    element.addEventListener('change', onSelect);
    element.click();
  }

  render() {
    return (
      <input className="open-file-dialog" type="file" />
    );
  }
}

export default OpenFileDialog;