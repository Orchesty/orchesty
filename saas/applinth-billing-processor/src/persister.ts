import { detailedDiff } from 'deep-object-diff';
import { Collection } from 'mongodb';
import { logger, now } from './main';

export enum PersisterMode {
    GENERATE = 1,
    REGENERATE = 2,
    DRY_RUN = 3,
}

enum CompareResult {
    MATCH = 1,
    NO_MATCH = 2,
    TAIL = 3,
}

// todo: replace with full document interface used across the codebase
interface PartialBillingDoc {
    installId: string;
    _id?: object;
}

function compareDocs(persisted: PartialBillingDoc, generated: PartialBillingDoc): CompareResult {
    // preprocess compared objects
    const p = {
        ...persisted,
        _id: undefined,
    };

    const g = {
        ...generated,
        _id: undefined,
    };

    // tail records will always differ by design
    if ('estimatedCost' in persisted) {
        return CompareResult.TAIL;
    }

    const r = detailedDiff(p, g);

    if (Object.keys(r.added).length + Object.keys(r.deleted).length + Object.keys(r.updated).length === 0) {
        return CompareResult.MATCH;
    }

    logger.error({ msg: 'diff:', ...r });
    return CompareResult.NO_MATCH;
}

// TODO rich mozna zmenit id na neco, z ceho uz zjistime ze uz to v DB je, treba endUser, app a cas (deterministicke id)
export async function persist(
    billingDocGenerator: unknown[],
    collection: Collection,
    mode: PersisterMode,
    instanceId: string,
    lastHighestDateTimestamp: string,
): Promise<void> {
    const dryRunInfo = mode === PersisterMode.DRY_RUN ? '(DRY RUN)' : '';

    const persisted = collection
        // .find({ instanceId, created: { $gt: lastHighestDateTimestamp } })
        .find({ instanceId })
        .sort({ _id: 1 }); // todo: we need some monotonic ordering key for sorting purposes, this will kick our ass one day

    // iterate through newly generated and previously persisted documents side by side
    for (const gDoc of billingDocGenerator) {
        // This is valid use of cursor, it fetches documents in batches internally
        // eslint-disable-next-line no-await-in-loop
        const pDoc = await persisted.hasNext() ? await persisted.next() : null;
        if (pDoc !== null) {
            const cr = compareDocs(pDoc as PartialBillingDoc, gDoc as PartialBillingDoc);
            if (cr === CompareResult.NO_MATCH) {
                logger.error({ msg: 'NO MATCH!', persisted: pDoc, generated: gDoc });
                if (mode !== PersisterMode.DRY_RUN) {
                    throw new Error('Failing on first error, to see more try --dry-run');
                }
            } else if (cr === CompareResult.TAIL) {
                logger.info(`replace tail ${dryRunInfo}`);
                logger.debug({ pDoc, gDoc });
                if (mode !== PersisterMode.DRY_RUN) {
                    // optimize: use batch operation
                    // eslint-disable-next-line no-await-in-loop
                    const r = await collection.replaceOne({ _id: pDoc._id }, gDoc as object);
                    if (r.matchedCount !== 1) {
                        throw new Error(`Update must match one document (id: ${pDoc.id.toString()})`);
                    }
                }
            } else {
                logger.info('match, skip');
            }
        } else {
            logger.info(`insert ${dryRunInfo}`);
            logger.debug({ gDoc });
            if (mode !== PersisterMode.DRY_RUN) {
                // optimize: use batch operation
                // eslint-disable-next-line no-await-in-loop
                await collection.insertOne(gDoc as object);
            }
        }
    }
}

export async function upsertMetadata(
    collection: Collection,
    tenantId: string,
    instanceId: string,
    highestDate: string,
): Promise<void> {
    await collection.updateOne({ tenantId }, {
        $set: {
            [`instances.${instanceId}.billingHistoryStart`]: new Date('2022-10-01T00:00:00.000Z'), // todo: determine
            [`instances.${instanceId}.billingHistoryEnd`]: new Date(now),
            [`instances.${instanceId}.lastRunHighestEventTimestamp`]: highestDate,
        },
    }, { upsert: true });
}
