import { start } from '../src';
import { beeceptorAppInstall, flexiBeeAppInstall, mySqlAppInstall, wflowAppInstall } from './DataProvider';

let prepared = false;

export async function prepare(): Promise<void> {
    if (prepared) {
        return;
    }

    await start();
    beeceptorAppInstall();
    wflowAppInstall();
    flexiBeeAppInstall();
    mySqlAppInstall();

    prepared = true;
}
