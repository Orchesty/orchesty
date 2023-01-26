function getEnv(env: string, defaultValue?: string): string {
    const e = process.env[env] ?? defaultValue;

    if (!e) {
        throw new Error(`Env [${env}] is missing.`);
    }

    return e;
}

export const app = {
    port: process.env.APP_PORT ?? 3000,
    debug: process.env.NODE_ENV === 'debug',
    env: process.env.NODE_ENV ?? 'debug',
    openapiPath: './openapi.yaml',
};

export const mongo = {
    dsn: getEnv('MONGODB_DSN'),
};
