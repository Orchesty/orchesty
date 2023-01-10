const { MongoClient } = require("mongodb");

let usdb = null;

module.exports.init = async (config) => {
    console.log('Connecting to MongoDB... ');
    const client = new MongoClient(config.mongodb_dsn, {
        maxPoolSize: 5,
        heartbeatFrequencyMS: 5000,
    });

    try {
        await client.connect();
        await client.db('admin').command({ ping: 1 });

        usdb = client.db();

        console.log('success!');
    } catch (e) {
        await client.close();

        // todo: reconnect
        throw e;
    }
}

module.exports.getUSDb = () => {
    if (usdb !== null) {
        return usdb;
    }
    throw new Error('usdb instance not initialized');
}