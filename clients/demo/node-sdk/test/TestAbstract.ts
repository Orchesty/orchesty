import { start } from '../src';
import { beeceptorAppInstall } from './DataProvider';

let prepared = false;

export function prepare(): void {
    if (prepared) {
        return;
    }

    start();
    beeceptorAppInstall();

    prepared = true;
}
