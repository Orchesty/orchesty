import React from 'react'

import ActionButtonPanel from 'elements/actions/ActionButtonPanel';
import SideBarButton from 'components/sideBar/SideBarButton';
import PageTabBar from 'rootApp/views/components/page/PageTabBar';

import './Page.less';

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
              <PageTabBar />
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