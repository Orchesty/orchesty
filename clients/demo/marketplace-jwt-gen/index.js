import { importSPKI, EncryptJWT } from 'jose';
import { question, keyInSelect } from 'readline-sync';
import { readFileSync } from 'fs';

const environments = {
    0: ['hanaboso-prod-pub.pem', 'https://applinth-hm8rohmtf6.eu1.cloud.orchesty.io/'], // prod
}

console.info("Usage: pnpm start [<eu_sub> <eu_alias>]\n");
console.info("Script expects hanaboso-prod-pub.pem and hanaboso-stage-pub.pem public keys in current working path\n");

let name = "";
let alias = "";

if (process.argv.length === 4) {
    name = process.argv[2]
    alias = process.argv[3]
} else {
    name = question('Insert eu_sub: ');
    alias = question('Insert eu_alias: ');
}
// const env = keyInSelect(['Production', 'Stage'], 'Select environment: ');
// const [pem, url] = environments[env];

const [pem, url] = environments[0];

importSPKI(readFileSync(pem, 'utf-8'), 'ECDH-ES').then((key) => {
  new EncryptJWT({
    sub: 'hanaboso',
    eu_sub: name,
    eu_alias: alias,
  })
    .setProtectedHeader({ alg: 'ECDH-ES', enc: 'A128GCM' })
    .setIssuedAt()
    .setExpirationTime('2h')
    .encrypt(key)
    .then((res, err) => {
      console.log(`${url}?u=${res}`)
      if (err) {
        console.error(err)
      }
    })
})
