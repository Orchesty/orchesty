import React from 'react';

export default props => {
  console.log(props);
  return (
    <div className="form-group">
      <label className="control-label col-md-3 col-sm-3 col-xs-12">{props.label}</label>
      <div className="col-md-9 col-sm-9 col-xs-12">
        <input type="text" className="form-control" placeholder={props.label} {...props.input} readOnly={props.readOnly}/>
      </div>
    </div>
  );
}
