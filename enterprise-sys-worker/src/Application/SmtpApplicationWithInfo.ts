import SmtpApplication from '@orchesty/connector-smtp/dist/SmtpApplication';
import path from 'path';

export default class SmtpApplicationWithInfo extends SmtpApplication {
    constructor() {
        super();
        this.infoFilename = path.join(__dirname, 'smtp-readme.md');
    }
}
