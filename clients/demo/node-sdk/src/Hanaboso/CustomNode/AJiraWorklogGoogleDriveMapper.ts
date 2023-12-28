import ACommonNode from '@orchesty/nodejs-sdk/dist/lib/Commons/ACommonNode';
import DateTimeUtils, { DATE_TIME } from '@orchesty/nodejs-sdk/dist/lib/Utils/DateTimeUtils';
import { DateTime } from 'luxon';

export default abstract class AJiraWorklogGoogleDriveMapper extends ACommonNode {

  protected convertSecondsToString(seconds: number): string {
    let hours: number | string = Math.floor(seconds / 3600);
    let minutes: number | string = Math.floor((seconds - hours * 3600) / 60);
    let innerSeconds: number | string = seconds - hours * 3600 - minutes * 60;

    if (hours < 10) {
      hours = `0${hours}`;
    }

    if (minutes < 10) {
      minutes = `0${minutes}`;
    }

    if (innerSeconds < 10) {
      innerSeconds = `0${innerSeconds}`;
    }

    return `${hours}:${minutes}:${innerSeconds}`;
  }

  protected convertDateTimeToString(dateTime: string): string {
    return DateTimeUtils.getFormattedDate(DateTime.fromISO(dateTime), DATE_TIME);
  }

}
