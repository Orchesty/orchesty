class Container {

    private services: { [key: string]: any } = {};

    public set(key: string, instance: any) {
        this.services[key] = instance;
    }

    public has(key: string): any|undefined {
        return !!this.services[key];
    }

    public get(key: string): any|undefined {
        if (this.has(key)) {
            return this.services[key];
        }

        throw new Error(`Trying to get non-existing service from container "${key}".`);
    }

}

export default Container;
