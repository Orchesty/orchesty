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
    // metricsDb: getEnv('METRICS_DB', undefined), TODO: use for v2.1.0 orchesty
    metricsDb: process.env.METRICS_DB,
};

export const fluentdOptions = {
    fluentdDsn: getEnv('FLUENTD_DSN'),
};
