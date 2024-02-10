import { init, listen as l } from '../src';

export async function listen(): Promise<void> {
    return init().then((value) => {
        l(value.app);
    });
}
