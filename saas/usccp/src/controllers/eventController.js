const storage = require("../storage.js");

module.exports.putEvent = async (args, res) => {
    const usdb = storage.getUSDb();
    const eventsCol = usdb.collection('Events');
    const payload = args.body;

    if (await eventsCol.findOne({ iid: payload.iid, created: payload.created })) {
        console.log(['dup', payload]);
        // skip dupes silently
        return res.send({ status: 'ok' });
    }

    console.log(['insert', payload]);
    await eventsCol.insertOne({
        created: payload.created,
        iid: payload.iid,
        type: payload.type,
        version: payload.version,
        data: payload.data, // todo validate by type?
    });

    return res.send({ status: 'ok' });
}