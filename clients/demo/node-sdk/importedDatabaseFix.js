const { MongoClient } = require('mongodb');

const MONGO_DSN = 'mongodb://mongo/demo';
const METRICS_DSN = 'mongodb://mongo/metrics';
const BATCH_SIZE = 25000;

function fmt(n) {
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

function fmtTime(totalSeconds) {
    const h = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
    const m = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
    const s = String(totalSeconds % 60).padStart(2, '0');
    return `${h}:${m}:${s}`;
}

async function buildNodeToTopologyMap(demoDb) {
    const map = {};
    const cursor = demoDb.collection('Node').aggregate([
        { $project: { nodeIdStr: { $toString: '$_id' }, topology: 1 } },
    ]);

    for await (const node of cursor) {
        if (node.topology) {
            map[node.nodeIdStr] = node.topology;
        }
    }

    return map;
}

async function fixCollection(metricsDb, collectionName, nodeToTopologyMap) {
    const label = collectionName.charAt(0).toUpperCase() + collectionName.slice(1);
    const collection = metricsDb.collection(collectionName);
    const filter = {
        $or: [
            { 'tags.topology_id': { $exists: false } },
            { 'tags.topology_id': null },
            { 'tags.topology_id': '' },
        ],
    };

    const totalDocs = await collection.countDocuments(filter);
    console.log(`${label}: ${fmt(totalDocs)} documents to process`);

    let totalUpdated = 0;
    let totalSkipped = 0;
    let bulkOps = [];
    let lastLogAt = 0;
    const startedAt = Date.now();

    function logProgress() {
        const processed = totalUpdated + totalSkipped;
        const pctDone = totalDocs > 0 ? ((processed / totalDocs) * 100).toFixed(2) : '0.00';
        const pctUpd = processed > 0 ? ((totalUpdated / processed) * 100).toFixed(2) : '0.00';
        const pctSkip = processed > 0 ? ((totalSkipped / processed) * 100).toFixed(2) : '0.00';

        let eta = '--:--:--';
        if (processed > 0 && processed < totalDocs) {
            const elapsedMs = Date.now() - startedAt;
            const remainingSec = Math.round(((totalDocs - processed) / processed) * (elapsedMs / 1000));
            eta = fmtTime(remainingSec);
        }

        console.log(
            `${label}: Processed ${fmt(processed)} / ${fmt(totalDocs)} (${pctDone}%) [${fmt(totalUpdated)} (${pctUpd}%) updated, ${fmt(totalSkipped)} (${pctSkip}%) skipped] { ETA: ${eta} }`,
        );
    }

    const cursor = collection.find(filter);

    for await (const doc of cursor) {
        const topologyId = nodeToTopologyMap[doc.tags?.node_id];
        if (topologyId) {
            bulkOps.push({
                updateOne: {
                    filter: { _id: doc._id },
                    update: { $set: { 'tags.topology_id': topologyId } },
                },
            });
        } else {
            totalSkipped++;
        }

        if (bulkOps.length >= BATCH_SIZE) {
            const result = await collection.bulkWrite(bulkOps);
            totalUpdated += result.modifiedCount;
            bulkOps = [];
        }

        const processed = totalUpdated + totalSkipped;
        if (processed - lastLogAt >= BATCH_SIZE) {
            logProgress();
            lastLogAt = processed;
        }
    }

    if (bulkOps.length > 0) {
        const result = await collection.bulkWrite(bulkOps);
        totalUpdated += result.modifiedCount;
    }

    logProgress();

    return { totalUpdated, totalSkipped };
}

async function main() {
    const demoClient = new MongoClient(MONGO_DSN);
    const metricsClient = new MongoClient(METRICS_DSN);

    try {
        await demoClient.connect();
        await metricsClient.connect();

        const demoDb = demoClient.db();
        const metricsDb = metricsClient.db();

        const globalStart = Date.now();

        console.log('Building node -> topology map...');
        const nodeToTopologyMap = await buildNodeToTopologyMap(demoDb);
        console.log(`Loaded ${fmt(Object.keys(nodeToTopologyMap).length)} node-to-topology mappings.\n`);

        const collections = ['connectors', 'pipes_node'];

        for (const name of collections) {
            const { totalUpdated, totalSkipped } = await fixCollection(metricsDb, name, nodeToTopologyMap);
            console.log(`Done. Updated: ${fmt(totalUpdated)}, Skipped: ${fmt(totalSkipped)}\n`);
        }

        const elapsedSec = Math.round((Date.now() - globalStart) / 1000);
        console.log(`All done in ${fmtTime(elapsedSec)}.`);
    } finally {
        await demoClient.close();
        await metricsClient.close();
    }
}

main().catch((err) => {
    console.error('Error:', err);
    process.exit(1);
});
