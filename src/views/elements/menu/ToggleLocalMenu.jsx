import React from 'react'

import LocalMenu from './LocalMenu';

class ToggleLocalMenu extends React.Component {
  constructor(props) {
    super(props);
    this._close = this.close.bind(this);
    this._click = this.click.bind(this);
  }

  componentDidMount(){
    document.addEventListener('mousedown', this._click);
    document.addEventListener('ontouchstart', this._click);
    // document.addEventListener('scroll', this._close);
    window.addEventListener('resize', this._close);
    document.addEventListener('contextmenu', this._close);
  }

  componentWillUnmount(){
    document.removeEventListener('mousedown', this._click);
    document.removeEventListener('ontouchstart', this._click);
    //document.removeEventListener('scroll', this._close);
    window.removeEventListener('resize', this._close);
    document.removeEventListener('contextmenu', this._close);
  }

  click(e){
    if (!this._self.contains(e.target)){
      this.close();
    }
  }
  
  close(){
    this.props.onClose();
  }

  render() {
    const {right, items} = this.props;
    return (
      <div ref={self => {this._self = self}}  className={'open' + (right ? ' pull-right' : '')}>
        <LocalMenu items={items} right={right} onAction={this._close} />
      </div>
    );
  }
}

ToggleLocalMenu.defaultProps = {
  right: false
};

export default ToggleLocalMenu;