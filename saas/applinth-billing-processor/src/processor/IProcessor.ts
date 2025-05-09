interface IProcessor {
    process(metadataRecord: Record<string, unknown>): Promise<Record<string, unknown>>;
}

export default IProcessor;
