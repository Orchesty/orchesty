import {menuItemType} from 'rootApp/types';
import * as applicationActions from 'rootApp/actions/applicationActions';

export default dispatch => [
  {
    type: menuItemType.SUB_MENU,
    caption: 'File',
    items: [
      {
        type: menuItemType.ACTION,
        caption: 'New topology',
        action: () => dispatch(applicationActions.openModal('topology_edit', {addNew: true}))
      },
      {
        type: menuItemType.ACTION,
        caption: 'New category',
        action: () => dispatch(applicationActions.openModal('category_edit', {addNew: true}))
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