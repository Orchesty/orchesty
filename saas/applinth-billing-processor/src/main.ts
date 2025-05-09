import Services from './DIContainer/Services';
import { command, container, initServices, logger } from './index';
import Mongo from './storage/mongo/Mongo';

(async function() {
    await initServices();
    await command();

    logger.debug({ memoryUsage: process.memoryUsage() });
    await container.get<Mongo>(Services.MONGO).disconnect();

    process.exit(0);
}()).catch((e: unknown) => {
    logger.error(e);
    process.exit(1);
});
