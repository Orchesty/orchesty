import React from 'react';
import ContainerType from '../../../../view_models/content/container_type';

function ContainerTypeButton(props){
  "use strict";
  return <button type="button" className="btn btn-info container-type-btn" onClick={props.onClick}>
    {props.type == ContainerType.PAGE ? 'Page mode' : 'Window mode'}
  </button>
}

export default ContainerTypeButton;