import { container, initServices, logger } from '../src';
import Services from '../src/DIContainer/Services';
import Mongo from '../src/storage/mongo/Mongo';
import { createFixtureData } from './dataProvider';

initServices().then(async () => {
    await createFixtureData();
    await container.get<Mongo>(Services.MONGO).disconnect();
}).catch((e) => {
    logger.error(e);
});
