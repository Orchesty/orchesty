import {MongoClient} from "mongodb";

export class Sender {

    private client: Promise<MongoClient>;

    /**
     *
     * @param {string} host
     * @param {number} port
     * @param {string} collection
     */
    public constructor(
        private host: string,
        private port: number,
        private readonly collection: string,
    ) {

        this.client = new MongoClient(`mongodb://${host}:${port}`, {useNewUrlParser: true, useUnifiedTopology: true}).connect();
        this.collection = collection;
    }

    /**
     * Sends UDP packet with message
     *
     * @param message
     * @return {Promise}
     */
    public send(message: {}): Promise<string> {
        return new Promise((resolve, reject) => {
            this.client.then(
                (client) => {
                    if (!client.isConnected()) {
                        client.connect((err: Error) => {
                            if (err !== null) {
                                return reject(`Failed to connect to MongoDB. Error: ${err}`);
                            }
                        });
                    }

                    const db = client.db("metrics");
                    const collection = db.collection(this.collection);

                    collection.insertOne(message, (error) => {
                        if (error !== null) {
                            return reject(`Failed to insert document. Error: ${error}`);
                        }
                    });

                    resolve(JSON.stringify(message));
                },
                (err) => {
                    if (err !== null) {
                        return reject(`Failed to connect to MongoDB. Error: ${err}`);
                    }
                },
            );
        });
    }

}
