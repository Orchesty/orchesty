import { init, listen } from './index';

// eslint-disable-next-line @typescript-eslint/no-floating-promises
init().then((value) => {
    listen(value.app);
});
