function getEnv(env: string, defaultValue?: string): string {
    const e = process.env[env] ?? defaultValue;
    if (!e) {
        throw new Error(`Env [${env}] is missing.`);
    }
    return e;
}

function getEnvKb(env: string, defaultValue: number): number {
    const kb = parseInt(process.env[env] ?? '', 10);

    return (Number.isNaN(kb) || kb <= 0 ? defaultValue : kb) * 1_024;
}

export const appOptions = {
    port: parseInt(process.env.APP_PORT ?? '8080', 10),
    debug: process.env.APP_ENV === 'debug' || process.env.NODE_ENV === 'debug',
    env: process.env.APP_ENV ?? process.env.NODE_ENV ?? 'debug',
    corsOrigin: getEnv('CORS_ORIGIN', '*'),
    maxRequestBodyBytes: getEnvKb('MAX_REQUEST_BODY_KB', 100),
    maxLoggerRequestBodyBytes: getEnvKb('MAX_LOGGER_REQUEST_BODY_KB', 2_048),
};

export const mongoOptions = {
    mongoDsn: getEnv('MONGODB_DSN'),
    logsCollection: getEnv('LOGS_COLLECTION', 'Logs'),
    metricsDb: getEnv('METRICS_DB'),
};

export const lokiOptions = {
    dsn: process.env.LOKI_DSN ?? '',
};
