const jose = require('jose')

const privateKey = `-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAs+HPFVblO+B1msyECGgH
Ff9AqwQXGiqjepCQHz073EOzvKjEmjjQXHVORK3ub7pEb4pbwUdhp5mfXpAKktpE
N4WZqSwa54VqQRSIkd7fa+BEgz8Ov35HnMGev4fB5B5nxqj12q4jCovqzkTmMhzh
o1+Nz9PmMvOZDZ6ZPTo/5wMgTyM1uE9lnPWKS7QwjUNGKsjFhlrJeYgarYj1WHcY
QjAWwtaU3JCaou4lIST8AtCPgxmEWBEgIURfFzonr9k8ykEJLyldxfnmjmUfPBDB
+RGZSDtJsWbp75VqAGO9zeBQUbhKft8J3LWF+L9/xhS+QKq0UQ99yuFPWbYcAUBt
1wIDAQAB
-----END PUBLIC KEY-----`

jose.importSPKI(privateKey, 'RSA-OAEP-256').then((key) => {
  new jose.EncryptJWT({
    sub: 'tenant_id',
    eu_sub: 'endUser',
    eu_alias: 'end_user_human_readable_alias_name',
  })
    .setProtectedHeader({ alg: 'RSA-OAEP-256', enc: 'A128GCM' })
    .setIssuedAt()
    .setExpirationTime('2h')
    .encrypt(key)
    .then((res, err) => {
      console.log(res)
      if (err) {
        console.error(err)
      }
    })
})
