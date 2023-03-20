export default interface User {
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
