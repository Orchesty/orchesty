#!/usr/bin/env node

import { readFileSync, writeFileSync, mkdirSync, readdirSync, statSync } from 'fs'
import { join, relative, dirname, basename } from 'path'
import matter from 'gray-matter'
import MiniSearch from 'minisearch'

const HELP_SRC = process.argv[2] || './Help'
const OUT_DIR = process.argv[3] || './help-dist'

const SKIP_DIRS = new Set(['scripts', 'node_modules'])

const pages = []

function stripMarkdown(md) {
  return md
    .replace(/^---[\s\S]*?---\n*/m, '')
    .replace(/```[\s\S]*?```/g, '')
    .replace(/`[^`]+`/g, '')
    .replace(/!\[.*?\]\(.*?\)/g, '')
    .replace(/\[([^\]]+)\]\(.*?\)/g, '$1')
    .replace(/#{1,6}\s+/g, '')
    .replace(/[*_~]{1,3}/g, '')
    .replace(/^\|.*$/gm, '')
    .replace(/^[-|:\s]+$/gm, '')
    .replace(/^>\s+/gm, '')
    .replace(/^[-*+]\s+/gm, '')
    .replace(/^\d+\.\s+/gm, '')
    .replace(/\n{3,}/g, '\n\n')
    .trim()
}

function walk(dir) {
  for (const entry of readdirSync(dir)) {
    if (entry.startsWith('_') || entry.startsWith('.')) continue
    const full = join(dir, entry)
    const stat = statSync(full)

    if (stat.isDirectory()) {
      if (SKIP_DIRS.has(entry)) continue
      walk(full)
    } else if (entry.endsWith('.md')) {
      const raw = readFileSync(full, 'utf-8')
      const { data, content } = matter(raw)
      const rel = relative(HELP_SRC, full).replace(/\.md$/, '')
      const slug = rel.replace(/\\/g, '/')

      const page = {
        slug,
        title: data.title || basename(rel),
        helpId: data.helpId || slug,
        order: data.order ?? 999,
        parent: dirname(slug) === '.' ? null : dirname(slug),
        content,
        text: stripMarkdown(raw),
      }

      pages.push(page)

      const pageOutDir = join(OUT_DIR, 'pages', dirname(slug))
      mkdirSync(pageOutDir, { recursive: true })
      writeFileSync(
        join(OUT_DIR, 'pages', slug + '.json'),
        JSON.stringify({
          title: page.title,
          helpId: page.helpId,
          content: page.content,
        }),
      )
    }
  }
}

walk(HELP_SRC)

pages.sort((a, b) => {
  if (a.parent !== b.parent) return (a.parent || '').localeCompare(b.parent || '')
  return a.order - b.order
})

const manifest = pages.map(({ slug, title, helpId, order, parent }) => ({
  slug,
  title,
  helpId,
  order,
  parent,
}))

mkdirSync(OUT_DIR, { recursive: true })
writeFileSync(join(OUT_DIR, 'manifest.json'), JSON.stringify(manifest, null, 2))

const searchIndex = new MiniSearch({
  fields: ['title', 'text'],
  storeFields: ['title', 'slug', 'helpId'],
  searchOptions: {
    boost: { title: 3 },
    fuzzy: 0.2,
    prefix: true,
  },
})

searchIndex.addAll(
  pages.map((p) => ({
    id: p.slug,
    title: p.title,
    slug: p.slug,
    helpId: p.helpId,
    text: p.text,
  })),
)

writeFileSync(join(OUT_DIR, 'search-index.json'), JSON.stringify(searchIndex.toJSON()))

console.log(`Built ${pages.length} help pages -> ${OUT_DIR}`)
