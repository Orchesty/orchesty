import Ajv from 'ajv/dist/jtd';

// nicetohave: generate from code, centralize and share (with USCCP, ...)

export const ajv = new Ajv();

// TODO rich mozna prepsat na JOI
export const applinthEndUserAppEventsV1 = ajv.compile({
    properties: {
        type: {
            type: 'string',
        },
        version: {
            type: 'uint32',
        },
        created: {
            type: 'timestamp',
        },
        iid: {
            type: 'string',
        },
        data: {
            ref: 'data',
        },
    },
    definitions: {
        data: {
            properties: {
                aid: {
                    type: 'string',
                },
                euid: {
                    type: 'string',
                },
            },

        },
    },
});
