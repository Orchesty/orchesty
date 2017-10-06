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

    if (this.props.readOnly) {
      return;
    }

    this._input.click();
  }


  render(){
    const {input, label, readOnly, description, input: {value}} = this.props;

    return (
          <div>
            <div className={'icheckbox_flat-green' + (value ? ' checked' : '') + (readOnly ? ' disabled' : '')}>
              <input type="checkbox" className="flat" placeholder={label} {...input} readOnly={readOnly} ref={this._setInput} checked={value} />
              <ins className="iCheck-helper"onClick={this._redirectClick}/>
            </div>
            {description && <span className="icheckbox-description">{description}</span>}
          </div>
    );
  }
}

export default CheckBoxInput;
