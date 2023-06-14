export default interface ICloudBridge {
    create(instanceDisplayName: string): Promise<string>;
    remove(instanceDisplayName: string): Promise<boolean>;
}
