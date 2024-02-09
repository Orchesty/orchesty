import bodyParser from 'body-parser';
import express from 'express';
import { EncryptJWT, importSPKI } from 'jose';
import * as process from 'process';

const app = express();
const port = process.env.APP_PORT;

app.use(express.static(`${__dirname}/`));
app.use(bodyParser.urlencoded({ extended: true }));

app.set('view engine', 'ejs');
app.set('views', __dirname);

app.get('/', async (req, res) => {
    const { u } = req.query as { u: string | undefined };

    const key = await importSPKI(process.env.JWE_PUBLIC_KEY ?? '', 'ECDH-ES');
    // eslint-disable-next-line @typescript-eslint/naming-convention
    const token = await new EncryptJWT({ sub: process.env.TENANT ?? 'tenant', eu_sub: u ?? 'hanaboso-user', eu_alias: 'Name' })
        .setProtectedHeader({ alg: 'ECDH-ES', enc: 'A128GCM' })
        .setIssuedAt()
        .setExpirationTime('2h')
        .encrypt(key);

    res.render('index.ejs', { url: `${process.env.APPLINTH_HOST}?u=${token}` });
});

app.listen(port, () => {
    // eslint-disable-next-line no-console
    console.log(`App listening on port ${port}`);
});
