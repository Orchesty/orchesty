import React from 'react'

class LocalMenuItem extends React.Component {
  constructor(props) {
    super(props);
  }

  makeAction(e){
    e.stopPropagation();
    e.preventDefault();
    const {item, onAction} = this.props;
    if (typeof item.action == 'function'){
      item.action();
      if (typeof onAction == 'function'){
        onAction(this, item);
      }
    }
  }

  render() {
    return <li><a href="" onClick={this.makeAction.bind(this)}>{this.props.item.caption}</a></li>
  }
}

export default LocalMenuItem;