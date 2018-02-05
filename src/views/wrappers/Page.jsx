import React from 'react'

import ActionButtonPanel from 'elements/actions/ActionButtonPanel';
import TabBar from 'elements/tab/TabBar';
import SideBarButton from 'components/sideBar/SideBarButton';

import './Page.less';


const demoItems = [
  {
    id: '12',
    caption: 'Home'
  },
  {
    id: '13',
    caption: 'Profile'
  },
  {
    id: '14',
    caption: 'Profile'
  }
];

export default (WrappedComponent, PageHeader) => {
  
  class Page extends React.Component {
    constructor(props) {
      super(props);
      this.setActions = this.setActions.bind(this);
      this.state = {
        pageActions: null
      }
    }

    setActions(actions) {
      this.setState({
        pageActions: actions
      });
    }

    render() {
      const {pageActions} = this.state;
      const {...passProps} = this.props;
      return (
        <div className="page">
          <div className="page-top">
            <SideBarButton />
            <div className="page-top-nav">
              <TabBar items={demoItems} active={1} onClose={() => {alert('TODO close tab');}}/>
              <ActionButtonPanel items={pageActions} size="md" right={true}/>
            </div>
          </div>
          <div className="page-content">
            <WrappedComponent setActions={this.setActions} {...passProps} />
          </div>
        </div>
      );
    }
  }

  Page.displayName = `Page(${WrappedComponent.displayName || WrappedComponent.name || 'Component'})`;

  return Page;
}