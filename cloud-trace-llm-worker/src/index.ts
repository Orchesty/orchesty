import { container, initiateContainer } from '@orchesty/nodejs-sdk';
import { config } from 'dotenv';
import CloudTraceOpenAIApplication from './Application/CloudTraceOpenAIApplication';
import OpenAITrace from './Connector/OpenAITrace';

config();

export function prepare(): void {
    initiateContainer();

    const openAITrace = new OpenAITrace();
    const openAIApp = new CloudTraceOpenAIApplication(openAITrace);
    openAITrace.setApplication(openAIApp);
    container.setApplication(openAIApp);

    container.setNode(openAITrace, openAIApp);
}
