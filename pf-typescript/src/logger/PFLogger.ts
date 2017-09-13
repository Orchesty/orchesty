/* tslint:disable:no-console */

// Severities defined by RFC 5424
export enum PFLoggerSeverity {
    DEBUG = "debug",
    INFO = "info",
    NOTICE = "notice",
    WARNING = "warning",
    ERROR = "error",
    CRITICAL = "critical",
    ALERT = "alert",
    EMERGENCY = "emergency",
}

class PFLogger {

    constructor(
        private hostname: string,
        private type: string,
    ) {}

    public log(
        severity: string = PFLoggerSeverity.INFO,
        message: string,
        stacktrace: string = "",
    ): void {
       console.log(JSON.stringify({
           timestamp: Date.now(),
           severity,
           hostname: this.hostname,
           type: this.type,
           message: message.replace(/\r/g, "").replace(/\n/g, ""),
           stacktrace: stacktrace.replace(/\r/g, "").replace(/\n/g, ""),
       }));
    }

    public debug(message: string) {
        this.log(PFLoggerSeverity.DEBUG, message);
    }

    public info(message: string) {
        this.log(PFLoggerSeverity.INFO, message);
    }

    public notice(message: string) {
        this.log(PFLoggerSeverity.NOTICE, message);
    }

    public warn(message: string) {
        this.log(PFLoggerSeverity.WARNING, message);
    }

    public error(message: string, stacktrace?: string) {
        this.log(PFLoggerSeverity.ERROR, message, stacktrace);
    }

    public critical(message: string, stacktrace?: string) {
        this.log(PFLoggerSeverity.CRITICAL, message, stacktrace);
    }

    public alert(message: string, stacktrace?: string) {
        this.log(PFLoggerSeverity.ALERT, message, stacktrace);
    }

    public emergency(message: string, stacktrace?: string) {
        this.log(PFLoggerSeverity.EMERGENCY, message, stacktrace);
    }

}

export default PFLogger;
