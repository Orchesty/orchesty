import React from 'react';

import './CheckBoxInput.less';

class CheckBoxInput extends React.Component {
  constructor(){
    super();
    this._input = null;
    this._setInput = this.setInput.bind(this);
    this._redirectClick = this.redirectClick.bind(this);
  }

  setInput(input){
    this._input = input;
  }

  redirectClick(e){
    e.preventDefault();
    this._input.click();
  }


  render(){
    const {input, label, readOnly, input: {value}} = this.props;

    return (
          <div className={'icheckbox_flat-green' + (value ? ' checked' : '') + (readOnly ? ' disabled' : '')}>
            <input type="checkbox" className="flat" placeholder={label} {...input} readOnly={readOnly} ref={this._setInput} checked={value} />
            <ins className="iCheck-helper"onClick={this._redirectClick}/>
          </div>
    );
  }
}

export default CheckBoxInput;
