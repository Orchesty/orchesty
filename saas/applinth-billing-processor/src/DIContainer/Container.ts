export default class DIContainer {

    private readonly services: Map<string, unknown>;

    public constructor() {
        this.services = new Map<string, unknown>();
    }

    // eslint-disable-next-line @typescript-eslint/no-unnecessary-type-parameters
    public get<T = unknown>(name: string): T {
        if (this.has(name)) {
            return this.services.get(name) as T;
        }

        throw new Error(`Service with name [${name}] does not exist!`);
    }

    public has(name: string): boolean {
        return this.services.has(name);
    }

    public set(name: string, service: unknown): void {
        if (!this.has(name)) {
            this.services.set(name, service);
        } else {
            throw new Error(`Service with name [${name}] already exist!`);
        }
    }

}
