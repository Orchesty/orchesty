const url =
  "https://raw.githubusercontent.com/Orchesty/orchesty-nodejs-connectors/master/appMetadata.json";

// eslint-disable-next-line @typescript-eslint/no-var-requires
const https = require("https");
// eslint-disable-next-line @typescript-eslint/no-var-requires
const fs = require("fs");

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
https.get(url, (resp) =>
  resp.pipe(fs.createWriteStream("public/metadata.json"))
);
