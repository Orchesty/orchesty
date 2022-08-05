import { getAuth, sendPasswordResetEmail } from 'firebase/auth';
import { auth } from 'firebase-admin';
import UserCreationError from '../errors/UserCreationError';
import { IUserCreateParams, IUserSearchQuery, IUserUpdateParams } from '../controllers/users';
import UserSearchError from '../errors/UserSearchError';
import { authApp, fbApp } from '../index';
import UserRecord = auth.UserRecord;
import SendLinkError from '../errors/SendLinkError';
import UserDeleteError from '../errors/UserDeleteError';

export default class UsersService {
  public async getUsersList(query: IUserSearchQuery, tenantId: string) {
    const tenantAuth = this._prepTenantAuth(query, tenantId);
    let users;

    try {
      if (query.emails) {
        const emails = query.emails.split(',').map((email) => ({ email }));

        users = await tenantAuth.getUsers(emails);
      } else {
        users = await tenantAuth.listUsers();
      }
    } catch (e) {
      throw new UserSearchError((e as Error).message);
    }

    return { rows: users.users.map(this._mapUserRecordToExport) };
  }

  public async getUser(query: IUserSearchQuery, tenantId: string) {
    const tenantAuth = this._prepTenantAuth(query, tenantId);
    let user = null;

    try {
      await tenantAuth.getUser(query.uid ?? '').then((userRecord) => {
        user = this._mapUserRecordToExport(userRecord);
      });
    } catch (e) {
      throw new UserSearchError((e as Error).message);
    }

    return { user };
  }

  public async createUser(query: IUserSearchQuery, userCreateParams: IUserCreateParams, tenantId: string) {
    const tenantAuth = this._prepTenantAuth(query, tenantId);
    let createdUser = null;

    try {
      await tenantAuth
        .createUser(userCreateParams)
        .then((userRecord) => {
          createdUser = this._mapUserRecordToExport(userRecord);
        });
    } catch (e) {
      throw new UserCreationError((e as Error).message);
    }

    await this.sendResetPasswordEmail(query, tenantId, userCreateParams.email);

    return { user: createdUser };
  }

  public async updateUser(query: IUserSearchQuery, userCreateParams: IUserUpdateParams, tenantId: string) {
    const tenantAuth = this._prepTenantAuth(query, tenantId);
    let updatedUser = null;

    try {
      await tenantAuth
        .updateUser(query.uid ?? '', userCreateParams)
        .then((userRecord) => {
          updatedUser = this._mapUserRecordToExport(userRecord);
        });
    } catch (e) {
      throw new UserCreationError((e as Error).message);
    }

    return { user: updatedUser };
  }

  public async deleteUser(query: IUserSearchQuery, tenantId: string) {
    const tenantAuth = this._prepTenantAuth(query, tenantId);

    try {
      await tenantAuth
        .deleteUser(query.uid ?? '');
    } catch (e) {
      throw new UserDeleteError((e as Error).message);
    }

    return { msg: 'User successfully deleted!' };
  }

  public async sendResetPasswordEmail(query: IUserSearchQuery, tenantId: string, email: string|null = null) {
    const fbAuth = getAuth(fbApp);
    fbAuth.tenantId = query.tenantId ?? tenantId;

    try {
      await sendPasswordResetEmail(fbAuth, email ?? query.email ?? '');
    } catch (e) {
      throw new SendLinkError((e as Error).message);
    }

    return { msg: 'Reset password link successfully sent!' };
  }

  private _mapUserRecordToExport = (user: UserRecord) => ({
    uid: user.uid,
    email: user.email,
    emailVerified: user.emailVerified,
    displayName: user.displayName ?? null,
    photoUrl: user.photoURL ?? null,
    phoneNumber: user.phoneNumber ?? null,
    disabled: user.disabled,
    metadata: {
      creationTime: user.metadata.creationTime,
      lastSignTime: user.metadata.lastSignInTime ?? null,
    },
    providerData: user.providerData,
    passwordHash: user.passwordHash,
    passwordSalt: user.passwordSalt,
    tokensValidAfterTime: user.tokensValidAfterTime,
    tenantId: user.tenantId ?? null,
  });

  private _prepTenantAuth = (query: IUserSearchQuery, tenantId: string) => authApp
    .auth().tenantManager().authForTenant(query.tenantId ?? tenantId);
}
