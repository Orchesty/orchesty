import React from 'react';

export default props => <li><a href="#" onClick={e => {e.preventDefault(); props.onItemClick(props.item)}}>{props.item.caption}</a></li>;

