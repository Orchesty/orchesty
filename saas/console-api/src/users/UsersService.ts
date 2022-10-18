import { getAuth, sendPasswordResetEmail } from 'firebase/auth';
import { auth } from 'firebase-admin';
import { TenantAwareAuth } from 'firebase-admin/lib/auth';
import { IUserCreateParams, IUserSearchQuery, IUserUpdateParams } from '../controllers/users';
import UserCreationError from '../errors/UserCreationError';
import UserSearchError from '../errors/UserSearchError';
import { authApp, db, fbApp } from '../index';
import UserRecord = auth.UserRecord;
import { CollectionEnum } from '../enums/CollectionEnum';
import NotFoundError from '../errors/NotFoundError';
import SendLinkError from '../errors/SendLinkError';
import UserDeleteError from '../errors/UserDeleteError';
import { ITenant } from '../tenants/TenantService';

export default class UsersService {

    public async getUsersList(query: IUserSearchQuery, gTenantId: string): Promise<{ rows: unknown }> {
        const tenantAuth = this.prepTenantAuth(query, gTenantId);
        let users;

        try {
            if (query.emails) {
                const emails = query.emails.split(',')
                    .map((email) => ({ email }));

                users = await tenantAuth.getUsers(emails);
            } else {
                users = await tenantAuth.listUsers();
            }
        } catch (e) {
            throw new UserSearchError((e as Error).message);
        }

        return { rows: users.users.map(this.mapUserRecordToExport.bind(this)) };
    }

    public async getUser(query: IUserSearchQuery, gTenantId: string): Promise<{ user: unknown }> {
        const tenantAuth = this.prepTenantAuth(query, gTenantId);
        let user = null;

        try {
            await tenantAuth.getUser(query.uid ?? '')
                .then((userRecord) => {
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
        gTenantId: string,
        tenantId: string,
    ): Promise<{ user: unknown }> {
        const tenantAuth = this.prepTenantAuth(query, gTenantId);
        let createdUser: IUser | undefined;

        try {
            await tenantAuth
                .createUser(userCreateParams)
                .then(async (userRecord) => {
                    let user = userRecord;
                    await tenantAuth.setCustomUserClaims(user.uid, {
                        customTenantId: userCreateParams.customTenantId ?? tenantId,
                    });
                    user = await tenantAuth.getUser(user.uid ?? '');
                    createdUser = this.mapUserRecordToExport(user);
                });
        } catch (e) {
            throw new UserCreationError((e as Error).message);
        }

        await this.sendResetPasswordEmail(query, gTenantId, userCreateParams.email);

        return { user: createdUser };
    }

    public async updateUser(
        query: IUserSearchQuery,
        userCreateParams: IUserUpdateParams,
        gTenantId: string,
    ): Promise<{ user: unknown }> {
        const tenantAuth = this.prepTenantAuth(query, gTenantId);
        let updatedUser: IUser | undefined;

        try {
            await tenantAuth
                .updateUser(query.uid ?? '', userCreateParams)
                .then(async (userRecord) => {
                    let user = userRecord;
                    if (userCreateParams.customTenantId) {
                        await tenantAuth.setCustomUserClaims(user.uid, {
                            customTenantId: userCreateParams.customTenantId,
                        });
                        user = await tenantAuth.getUser(user.uid ?? '');
                    }
                    updatedUser = this.mapUserRecordToExport(user);
                });
        } catch (e) {
            throw new UserCreationError((e as Error).message);
        }

        return { user: updatedUser };
    }

    public async deleteUser(query: IUserSearchQuery, gTenantId: string): Promise<{ msg: string }> {
        const tenantAuth = this.prepTenantAuth(query, gTenantId);

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
        gTenantId: string,
        email: string | null = null,
    ): Promise<{ msg: string }> {
        const fbAuth = getAuth(fbApp);
        fbAuth.tenantId = query.gTenantId ?? gTenantId;

        try {
            await sendPasswordResetEmail(fbAuth, email ?? query.email ?? '');
        } catch (e) {
            throw new SendLinkError((e as Error).message);
        }

        return { msg: 'Reset password link successfully sent!' };
    }

    public async getGTenantId(
        tenantId: string,
    ): Promise<{ gTenantId: string }> {
        const tenant = await db.getCloudCollection(CollectionEnum.TENANT).findOne({
            tenantId,
        }) as unknown as ITenant;

        if (tenant) {
            return { gTenantId: tenant.gTenantId };
        }
        throw new NotFoundError(`Tenant with given tenantId ${tenantId} not found!`);
    }

    private mapUserRecordToExport(user: UserRecord): IUser {
        const customTenantId = user.customClaims?.customTenantId ? user.customClaims?.customTenantId : undefined;
        return {
            uid: user.uid,
            email: user.email,
            emailVerified: user.emailVerified,
            displayName: user.displayName ?? undefined,
            photoUrl: user.photoURL ?? undefined,
            phoneNumber: user.phoneNumber ?? undefined,
            disabled: user.disabled,
            metadata: {
                creationTime: user.metadata.creationTime,
                lastSignTime: user.metadata.lastSignInTime ?? undefined,
            },
            providerData: user.providerData.map((item) => ({
                uid: item.uid,
                displayName: item.displayName ?? undefined,
                email: item.email ?? undefined,
                photoUrl: item.photoURL ?? undefined,
                providerId: item.providerId ?? undefined,
                phoneNumber: item.phoneNumber ?? undefined,
            })),
            passwordHash: user.passwordHash,
            passwordSalt: user.passwordSalt,
            tokensValidAfterTime: user.tokensValidAfterTime,
            tenantId: user.tenantId ?? undefined,
            customTenantId,
        };
    }

    private prepTenantAuth(query: IUserSearchQuery, gTenantId: string): TenantAwareAuth {
        return authApp
            .auth()
            .tenantManager()
            .authForTenant(query.gTenantId ?? gTenantId);
    }

}

export interface IUser {
    metadata: { lastSignTime: string; creationTime: string };
    providerData: {
        uid: string;
        photoUrl: string;
        phoneNumber: string;
        displayName: string;
        providerId: string;
        email: string;
    }[];
    displayName: string | undefined;
    passwordHash: string | undefined;
    uid: string;
    emailVerified: boolean;
    photoUrl: string | undefined;
    phoneNumber: string | undefined;
    tenantId: string | undefined;
    customTenantId: string | undefined;
    disabled: boolean;
    passwordSalt: string | undefined;
    tokensValidAfterTime: string | undefined;
    email: string | undefined;
}
