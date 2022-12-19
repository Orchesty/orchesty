export const appOptions = {
    port: parseInt(process.env.APP_PORT ?? '8080', 10),
    debug: process.env.APP_ENV === 'debug' || process.env.NODE_ENV === 'debug',
    env: process.env.APP_ENV ?? process.env.NODE_ENV ?? 'debug',
};
