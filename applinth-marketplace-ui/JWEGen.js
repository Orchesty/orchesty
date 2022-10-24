const jose = require("jose")

const publicKey = `-----BEGIN PUBLIC KEY-----
MIGbMBAGByqGSM49AgEGBSuBBAAjA4GGAAQAZyRJCpJxZ6cbpkhZOTBSBGE5tkXm
mpTuFKwnOEETiHIUyGWEW/RnkqLpwEbcZyXH2GvggaV8zm5izbzd7u+S9qQAzoOK
fhpIMLRXX0muR4TKEA9oxBJi96Lb8o3/IxebfLtmDxnF8KMCdr1kkOqcltexS1ap
lMtmVuVUqMJD6dvQr2E=
-----END PUBLIC KEY-----
`

jose.importSPKI(publicKey, "ECDH-ES").then((key) => {
  new jose.EncryptJWT({
    sub: "tenant",
    eu_sub: "endUser",
    eu_alias: "end_user_human_readable_alias_name",
  })
    .setProtectedHeader({ alg: "ECDH-ES", enc: "A128GCM" })
    .setIssuedAt()
    .setExpirationTime("2h")
    .encrypt(key)
    .then((res, err) => {
      console.log(res)
      if (err) {
        console.error(err)
      }
    })
})
