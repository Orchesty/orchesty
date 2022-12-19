import { expressApp as e, listen as l } from '../src';
export const expressApp = e;
export function listen(): void {
    l();
}
