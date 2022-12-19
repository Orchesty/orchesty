// This script transforms all json files with translations to a format that can
// be processed directly by vue-i18n-extract tool. It creates a gitignored
// directory with transformed json. It was fastest way to make the translation
// validation tool working for us. It is invoked by a script in package.json.

import fs from "fs/promises"
import path from "path"
import url from "url"

function relativePath(directoryName) {
  return url.fileURLToPath(new URL(directoryName, import.meta.url))
}

async function readTranslationsFiles() {
  const dir = relativePath("en")
  const files = await fs.readdir(dir)
  const promises = files
    .filter((file) => file.endsWith("json"))
    .map(async (file) => {
      const content = await fs.readFile(`${dir}/${file}`, "utf8")
      return content
    })
  const contents = await Promise.all(promises)
  return contents
}

async function createTransformedPath() {
  try {
    await fs.mkdir(relativePath("generated"))
  } catch (err) {
    if (err.code === "EEXIST") {
      return
    }
  }
}

async function process() {
  const contents = await readTranslationsFiles()
  let transformedTranslations = {}
  for (const content of contents) {
    const translation = JSON.parse(content)
    transformedTranslations = {
      ...transformedTranslations,
      ...translation,
    }
  }

  await createTransformedPath()
  await fs.writeFile(
    path.join(relativePath("generated"), "en.json"),
    JSON.stringify(transformedTranslations, null, 2)
  )
}

process()
