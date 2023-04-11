import { BillingHandler } from '../epochs';
import { State } from '../processor';

function daysInMonth(year: number, month: number): number {
    // note: .UTC() month is indexed from 0, but we want realMonth+1 to get
    // days in realMonth, so we leave "month" as is
    const d = new Date(Date.UTC(year, month, 0));
    return d.getUTCDate();
}

function isUTCMidnight(d: Date): boolean {
    return d.getUTCHours() === 0
        && d.getUTCMinutes() === 0
        && d.getUTCSeconds() === 0
        && d.getUTCMilliseconds() === 0;
}

export default class implements BillingHandler {

    private readonly dailyApps = new Set<string>();

    public computeCost(
        state: State,
        endUserId: string,
        appInstallId: string,
        start: Date,
        end: Date,
        price: number,
    ): number {
        const dim = daysInMonth(start.getUTCFullYear(), start.getMonth() + 1);
        const dailyPrice = price / dim;
        const { appId } = state.endUsers[endUserId].appInstalls[appInstallId];

        // todo: assert start/end is in range of a single month!

        // bill only days whe appInstall for this app is the first one
        const daysBillable = this.countDaysBillableAndMark(`${endUserId}|${appId}`, start, end);

        return Math.round(daysBillable * dailyPrice);
    }

    private countDaysBillableAndMark(daKeyPrefix: string, start: Date, end: Date): number {
        let days = 0;
        const endClone = new Date(end);
        if (isUTCMidnight(endClone)) {
            // at midnight, set time one hour back to ensure the right DoM is returned
            endClone.setUTCHours(-1);
        }
        for (let d = start.getUTCDate(); d <= endClone.getUTCDate(); d++) {
            const daKey = `${daKeyPrefix}-${d}`;
            if (!this.dailyApps.has(daKey)) {
                days++;
                this.dailyApps.add(daKey);
            }
        }
        return days;
    }

}
