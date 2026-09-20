#!/usr/bin/env node
/**
 * Publish clean markdown versions of the documentation pages into the
 * VitePress build output.
 *
 * The llms.txt spec (https://llmstxt.org/) recommends that pages provide a
 * clean markdown version at the same URL as the original page with `.md`
 * appended. VitePress only renders HTML, so we copy the source markdown
 * files into the dist directory after the build:
 *
 *   docs/guide/getting-started.md -> dist/guide/getting-started.html.md
 *   docs/guide/index.md           -> dist/guide/index.html.md
 *
 * The YAML frontmatter block is stripped so the published files are clean
 * markdown. Afterwards, every URL listed in the generated llms.txt is
 * checked against the published files, so a stale link fails the build
 * instead of silently breaking the site.
 *
 * Run after `vitepress build docs`:
 *   node scripts/publish-markdown.mjs
 */
import { readdir, readFile, writeFile, mkdir } from 'node:fs/promises'
import { join, dirname, relative, sep } from 'node:path'
import { fileURLToPath } from 'node:url'

const SITE_DIR = join(dirname(fileURLToPath(import.meta.url)), '..')
const DOCS_DIR = join(SITE_DIR, 'docs')
const DIST_DIR = join(DOCS_DIR, '.vitepress', 'dist')
const LLMS_TXT = join(DIST_DIR, 'llms.txt')

/** Recursively collect all markdown files under a directory. */
async function collectMarkdown(dir) {
  const files = []
  const entries = await readdir(dir, { withFileTypes: true })
  for (const entry of entries) {
    if (entry.name.startsWith('.') || entry.name === 'node_modules') continue
    const path = join(dir, entry.name)
    if (entry.isDirectory()) {
      files.push(...(await collectMarkdown(path)))
    } else if (entry.name.endsWith('.md')) {
      files.push(path)
    }
  }
  return files
}

/** Strip the YAML frontmatter block (if present), leaving clean markdown. */
function stripFrontmatter(content) {
  return content.replace(/^---\r?\n[\s\S]*?\r?\n---\r?\n?/, '')
}

/** Map a docs-relative markdown path to its published file name. */
function publishedPath(relPath) {
  const noExt = relPath.replace(/\.md$/, '')
  return noExt.endsWith('/index') ? `${noExt.slice(0, -'index'.length)}index.html.md` : `${noExt}.html.md`
}

/**
 * Verify that every URL in llms.txt maps to a file published in the dist
 * directory. Exits non-zero on any mismatch so CI catches stale links.
 */
async function verifyLinks() {
  let txt
  try {
    txt = await readFile(LLMS_TXT, 'utf8')
  } catch {
    console.error(`ERROR: ${LLMS_TXT} not found — run scripts/generate-llms-txt.mjs before this script`)
    process.exit(1)
  }

  const broken = []
  for (const match of txt.matchAll(/- \[[^\]]*\]\((https?:\/\/[^)]+)\)/g)) {
    const url = match[1]
    const path = url.replace(/^https?:\/\/[^/]+\/[^/]+/, '')
    const target = join(DIST_DIR, path)
    try {
      await readFile(target)
    } catch {
      broken.push(`${url} (missing ${target})`)
    }
  }

  if (broken.length > 0) {
    console.error(`ERROR: ${broken.length} broken link(s) in llms.txt:`)
    for (const b of broken) console.error(`  - ${b}`)
    process.exit(1)
  }
  console.log(`Verified ${txt.length > 0 ? [...txt.matchAll(/- \[[^\]]*\]\(/g)].length : 0} llms.txt links against published files`)
}

async function main() {
  const files = await collectMarkdown(DOCS_DIR)
  let count = 0

  for (const file of files) {
    const rel = relative(DOCS_DIR, file).split(sep).join('/')
    if (rel.includes('.vitepress/')) continue

    const target = join(DIST_DIR, publishedPath(rel))
    const content = stripFrontmatter(await readFile(file, 'utf8'))
    await mkdir(dirname(target), { recursive: true })
    // UTF-8 BOM ensures browsers decode the Chinese documentation correctly
    // even when the server omits a charset in the Content-Type header.
    await writeFile(target, '\uFEFF' + content, 'utf8')
    count++
  }

  console.log(`Published ${count} markdown files into ${DIST_DIR}`)
  await verifyLinks()
}

main().catch((err) => {
  console.error(err)
  process.exit(1)
})
