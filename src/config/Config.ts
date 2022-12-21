function getEnv(env: string, defaultValue?: string): string {
    const e = process.env[env] ?? defaultValue;
    if (!e) {
        throw new Error(`Env [${env}] is missing.`);
    }
    return e;
}

export const appOptions = {
    port: parseInt(process.env.APP_PORT ?? '8080', 10),
    debug: process.env.APP_ENV === 'debug' || process.env.NODE_ENV === 'debug',
    env: process.env.APP_ENV ?? process.env.NODE_ENV ?? 'debug',
    corsOrigin: getEnv('CORS_ORIGIN', '*'),
};

export const mongoOptions = {
    mongoDsn: getEnv('MONGODB_DSN'),
    collections: {
        apiToken: 'ApiToken',
    },
};
