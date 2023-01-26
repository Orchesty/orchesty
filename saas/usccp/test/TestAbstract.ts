import { container } from '../src';
import Services from '../src/DIContainer/Services';

export async function dropCollection(name: string): Promise<void> {
    const storage = container.get<Storage>(Services.STORAGE);
    const db = storage.getUSDb();

    try {
        await db.dropCollection(name);
    } catch (e) {
    }
}
