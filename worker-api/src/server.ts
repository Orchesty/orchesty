import { init, listen } from './index';

// eslint-disable-next-line @typescript-eslint/no-floating-promises
init().then((value) => {
    listen(value.app);

    function shutdown(): void {
        // eslint-disable-next-line @typescript-eslint/no-floating-promises
        value.loki.close().finally(() => process.exit(0));
    }

    process.on('SIGTERM', shutdown);
    process.on('SIGINT', shutdown);
});
