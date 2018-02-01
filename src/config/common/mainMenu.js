import {menuItemType} from 'rootApp/types';
import * as applicationActions from 'rootApp/actions/applicationActions';

export default dispatch => [
  {
    type: menuItemType.SUB_MENU,
    caption: 'File',
    items: [
      {
        type: menuItemType.ACTION,
        caption: 'New folder',
        action: () => alert('TODO')
      },
      {
        type: menuItemType.ACTION,
        caption: 'New topology',
        action: () => alert('TODO')
      }
    ]
  },
  {
    type: menuItemType.ACTION,
    caption: 'Authorizations',
    action: () => dispatch(applicationActions.selectPage('authorization_list'))
  },
  {
    type: menuItemType.ACTION,
    caption: 'Notifications',
    action: () => dispatch(applicationActions.selectPage('notification_settings'))
  }
];