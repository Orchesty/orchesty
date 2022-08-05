const jose = require('jose')

const privateKey = `-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCz4c8VVuU74HWa
zIQIaAcV/0CrBBcaKqN6kJAfPTvcQ7O8qMSaONBcdU5Ere5vukRvilvBR2GnmZ9e
kAqS2kQ3hZmpLBrnhWpBFIiR3t9r4ESDPw6/fkecwZ6/h8HkHmfGqPXariMKi+rO
ROYyHOGjX43P0+Yy85kNnpk9Oj/nAyBPIzW4T2Wc9YpLtDCNQ0YqyMWGWsl5iBqt
iPVYdxhCMBbC1pTckJqi7iUhJPwC0I+DGYRYESAhRF8XOiev2TzKQQkvKV3F+eaO
ZR88EMH5EZlIO0mxZunvlWoAY73N4FBRuEp+3wnctYX4v3/GFL5AqrRRD33K4U9Z
thwBQG3XAgMBAAECggEAKF05TL6M/dymRzAxSHmfbW4zoqxuSq7atDwQOxJQhmKi
yGjOhRTOnJCuGXc2E9gmVyki4cIUxbkRK/UCimVz/Ul7a5y8BMvJRgVHiAQM+nX+
qbzSoGHDzAceijf6aCfyfX+Ye5OrxUgUTmjjhsc4UqK0fbg85Z4H8Chwlm6lvD50
/htwa5BGiNRzkLA/ZMa5ppF9sm/rVmNWi9eK2pzaqKJivmcaoiwqZF1WVOhhmzG1
KT1GMwUe+HT84dK9XrMcFpYAKQKLH/HCustaF8D+j1ashOvpF4xtjUjZBS0iz8n1
MivhXW+Ck7eJxL1AuBBzaArJRPPxcHq8BoKqPS7B8QKBgQDdCuHfe4jfVaFfMkrc
TsAWnSj+BRHStmWhdKIH/k/s4Zq5pvL1ATn1xc4TDEqEu+Shc90NW4eUYvoL5/IP
rZ+znK0zYvegtzXi9OeSpBraj+ZzJR+Mudh+LqxdlZV9liKrFB5YGvge3sIK0tYv
dBan6tzmmG3kjXFUxQPVcYeqQwKBgQDQVIGzsz3ERCS1KJgnoJ7+/2HNfpxi5P+k
O+UI9VTuE2D6FE1Okx3icYymFC3x8LHrtdtNy1rA8vedQiiHrCblTkSU4i+CCVqU
kATls90k/njBdQWzFrmJ1t9CjiQSYleDiWlIU81LGMrBb6sJsOolpUAC2sOtJbCo
W/g1Fimm3QKBgF2rSuRleS8LHoM00LxjMstidgiPJWphmNe+kRtKDZyYTfT2Zmak
ymb4F8fCoaF17gDtFHOgoeejucpp1A4IyXBXqJ3qBn24pcEzfx6JJEgSStnolWIR
L0jphmlyBhNeF/rfX6x+YT7Tru7fQZyCWUd3I30kgw0jUy9U/bbpkU/5AoGAIXhC
Mj2swbh08UnpUAyFHtCmxN3/f//sdlVNEahgkbM5VFQoq2QFXBkEELaTPxh9bTIV
XqU6Gl+ummxDmLB2u0ZczFKecVTRYabVspW4BLaBbgs/9CrFeji0O7wcXXvBNZfA
+2bDR7pe8L7hCriKlau74fmFkG7Kt/G2qci6vl0CgYAJ8iwR4p6/+EQndy/6ZW2b
TRTnmX6NjFTHNLipclO/ltWx8CBaW76TeOGc4U6RwsqjytkT9PSI0x0BwiLM0HZ9
VdS1O1pcwFCNX4jSJBWQPje8IEEHzfUhW6AhVtQ1R+w5nrRDbMoaOeFfFGVEK98D
2cIvZthsD1I93JMh026Fig==
-----END PRIVATE KEY-----`

jose.importPKCS8(privateKey, 'RSA-OAEP-256').then((key) => {
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
