export interface Config {
    mongodbDSN: string;
    usDb: string;
    billingDb: string;
    billingAdminDb: string;
}

export const config: Config = {
    mongodbDSN: process.env.MONGODB_DSN ?? (() => {
        throw new Error('MONGODB_DSN not set');
    })(),
    usDb: process.env.MONGODB_USAGE_STATS_DB ?? 'usage-stats',
    billingDb: process.env.MONGODB_USAGE_STATS_DB ?? 'usage-stats',
    billingAdminDb: process.env.MONGODB_BILLING_ADMIN_DB ?? 'cloud',
};
