import { getAuth, sendPasswordResetEmail } from 'firebase/auth';
import { auth } from 'firebase-admin';
import { TenantAwareAuth } from 'firebase-admin/lib/auth';
import { IUserCreateParams, IUserSearchQuery, IUserUpdateParams } from '../controllers/users';
import UserCreationError from '../errors/UserCreationError';
import UserSearchError from '../errors/UserSearchError';
import { authApp, fbApp } from '../index';
import UserRecord = auth.UserRecord;
import SendLinkError from '../errors/SendLinkError';
import UserDeleteError from '../errors/UserDeleteError';

export default class UsersService {

    public async getUsersList(query: IUserSearchQuery, tenantId: string): Promise<{ rows: unknown }> {
        const tenantAuth = this.prepTenantAuth(query, tenantId);
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

        return { rows: users.users.map(this.mapUserRecordToExport.bind(this)) };
    }

    public async getUser(query: IUserSearchQuery, tenantId: string): Promise<{ user: unknown }> {
        const tenantAuth = this.prepTenantAuth(query, tenantId);
        let user = null;

        try {
            await tenantAuth.getUser(query.uid ?? '').then((userRecord) => {
                user = this.mapUserRecordToExport(userRecord);
            });
        } catch (e) {
            throw new UserSearchError((e as Error).message);
        }

        return { user };
    }

    public async createUser(
        query: IUserSearchQuery,
        userCreateParams: IUserCreateParams,
        tenantId: string,
    ): Promise<{ user: unknown }> {
        const tenantAuth = this.prepTenantAuth(query, tenantId);
        let createdUser = null;

        try {
            await tenantAuth
                .createUser(userCreateParams)
                .then((userRecord) => {
                    createdUser = this.mapUserRecordToExport(userRecord);
                });
        } catch (e) {
            throw new UserCreationError((e as Error).message);
        }

        await this.sendResetPasswordEmail(query, tenantId, userCreateParams.email);

        return { user: createdUser };
    }

    public async updateUser(
        query: IUserSearchQuery,
        userCreateParams: IUserUpdateParams,
        tenantId: string,
    ): Promise<{ user: unknown }> {
        const tenantAuth = this.prepTenantAuth(query, tenantId);
        let updatedUser = null;

        try {
            await tenantAuth
                .updateUser(query.uid ?? '', userCreateParams)
                .then((userRecord) => {
                    updatedUser = this.mapUserRecordToExport(userRecord);
                });
        } catch (e) {
            throw new UserCreationError((e as Error).message);
        }

        return { user: updatedUser };
    }

    public async deleteUser(query: IUserSearchQuery, tenantId: string): Promise<{ msg: string }> {
        const tenantAuth = this.prepTenantAuth(query, tenantId);

        try {
            await tenantAuth
                .deleteUser(query.uid ?? '');
        } catch (e) {
            throw new UserDeleteError((e as Error).message);
        }

        return { msg: 'User successfully deleted!' };
    }

    public async sendResetPasswordEmail(
        query: IUserSearchQuery,
        tenantId: string,
        email: string | null = null,
    ): Promise<{ msg: string }> {
        const fbAuth = getAuth(fbApp);
        fbAuth.tenantId = query.tenantId ?? tenantId;

        try {
            await sendPasswordResetEmail(fbAuth, email ?? query.email ?? '');
        } catch (e) {
            throw new SendLinkError((e as Error).message);
        }

        return { msg: 'Reset password link successfully sent!' };
    }

    private mapUserRecordToExport(user: UserRecord): unknown {
        return {
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
        };
    }

    private prepTenantAuth(query: IUserSearchQuery, tenantId: string): TenantAwareAuth {
        return authApp
            .auth().tenantManager().authForTenant(query.tenantId ?? tenantId);
    }

}
