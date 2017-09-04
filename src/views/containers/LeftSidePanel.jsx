import React from 'react'

import SideMenuPanel from './SideMenuPanel';

class LeftSidePanel extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    return (
      <div className="col-md-3 left_col">
        <div className="left_col scroll-view">
          <div className="navbar nav_title" style={{border: 0}}>
            <a href="#" className="site_title"><i className="fa fa-connectdevelop"></i> <span>Pipes manager</span></a>
          </div>
          <div className="clearfix"></div>
          <SideMenuPanel />
        </div>
      </div>
    );
  }
}

export default LeftSidePanel;