import React from 'react';

export default function (props){
  "use strict";
  return <span>{props.value ? 'Yes' : 'No'}</span>
}