#!/usr/bin/env node
/**
 * Generate `docs/public/llms.txt` for the PurePHP documentation site.
 *
 * llms.txt (https://llmstxt.org/) is a markdown file placed at the site root
 * that helps LLMs discover and navigate the documentation. Per the spec, its
 * links point to the clean markdown versions of the pages, served at the same
 * URL as the original page with `.md` appended (e.g. `guide/index.html.md`).
 *
 * The markdown files themselves are published into the build output by
 * `scripts/publish-markdown.mjs`, which runs after `vitepress build`.
 *
 * Usage: node scripts/generate-llms-txt.mjs
 * Output: site/docs/public/llms.txt (copied to the dist root by VitePress)
 */
import { readdir, readFile, writeFile, mkdir } from 'node:fs/promises'
import { join, dirname, relative, sep } from 'node:path'
import { fileURLToPath } from 'node:url'

const SITE_DIR = join(dirname(fileURLToPath(import.meta.url)), '..')
const DOCS_DIR = join(SITE_DIR, 'docs')
const OUTPUT = join(DOCS_DIR, 'public', 'llms.txt')

// The deployed site base URL (see docs/.vitepress/config.js -> base: '/purephp/').
const SITE_URL = 'https://yonld.github.io/purephp'

// Max description length in characters (keep llms.txt terse).
const DESC_MAX = 200

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

/** Parse the YAML frontmatter block (if present) into a plain object. */
function parseFrontmatter(content) {
  const match = content.match(/^---\r?\n([\s\S]*?)\r?\n---/)
  if (!match) return {}
  const fm = {}
  for (const line of match[1].split(/\r?\n/)) {
    const kv = line.match(/^([A-Za-z0-9_-]+):\s*(.*)$/)
    if (kv) fm[kv[1]] = kv[2].replace(/^['"]|['"]$/g, '').trim()
  }
  return fm
}

/** Strip markdown noise from a piece of text: code blocks, inline code, links. */
function cleanMarkdown(text) {
  return text
    .replace(/```[\s\S]*?```/g, ' ') // code blocks
    .replace(/`([^`]*)`/g, '$1') // inline code
    .replace(/\[([^\]]*)\]\([^)]*\)/g, '$1') // links
    .replace(/!?\[([^\]]*)\]\([^)]*\)/g, '$1') // images
    .replace(/[*_~#>|]/g, ' ') // formatting characters
    .replace(/\s+/g, ' ')
    .trim()
}

/** Extract the first `# H1` heading from the markdown body (after frontmatter). */
function extractTitle(content) {
  const body = content.replace(/^---\r?\n[\s\S]*?\r?\n---/, '')
  const match = body.match(/^#\s+(.+)$/m)
  return match ? cleanMarkdown(match[1]) : ''
}

/** Extract the first paragraph after the H1 as the page description. */
function extractDescription(content) {
  const body = content.replace(/^---\r?\n[\s\S]*?\r?\n---/, '')
  const afterH1 = body.replace(/^#\s+.+$/m, '')
  // First paragraph: consecutive non-empty lines that are not headings,
  // code fences, blockquotes, or horizontal rules.
  let paragraph = []
  for (const line of afterH1.split(/\r?\n/)) {
    const trimmed = line.trim()
    if (!trimmed) {
      if (paragraph.length > 0) break
      continue
    }
    if (
      /^#{1,6}\s/.test(trimmed) ||
      trimmed.startsWith('```') ||
      trimmed.startsWith('>') ||
      /^---+\s*$/.test(trimmed)
    )
      break
    paragraph.push(trimmed)
  }
  let desc = cleanMarkdown(paragraph.join(' '))
  // If the paragraph is longer than the limit, keep the longest run of
  // complete sentences that fits, searching backwards from the limit.
  if (desc.length > DESC_MAX) {
    const cut = desc.slice(0, DESC_MAX)
    let boundary = -1
    for (let i = cut.length - 1; i >= 0; i--) {
      if (/[.!?。！？]/.test(cut[i]) && (i + 1 >= cut.length || /\s/.test(cut[i + 1]))) {
        boundary = i
        break
      }
    }
    desc = boundary > 0 ? cut.slice(0, boundary + 1) : `${cut}…`
  }
  return desc
}

/**
 * Map a docs-relative markdown path to its deployed markdown URL.
 * Follows the llms.txt spec: same URL as the original page, with `.md`
 * appended; URLs without a file name get `index.html.md` appended.
 */
function pageUrl(relPath) {
  const noExt = relPath.replace(/\.md$/, '')
  const path = noExt.endsWith('/index') ? `${noExt.slice(0, -'index'.length)}index.html.md` : `${noExt}.html.md`
  return `${SITE_URL}/${path}`.replace(/([^:])\/+/g, '$1/')
}

/** Group label and ordering for each docs subtree. */
const SECTIONS = [
  { prefix: ['guide'], label: 'Guide' },
  { prefix: ['api'], label: 'API Reference' },
  { prefix: ['zh', 'guide'], label: '中文指南 (Chinese Guide)' },
  { prefix: ['zh', 'api'], label: '中文 API 参考 (Chinese API Reference)' },
]

/** Sort: index pages first, then alphabetically within each section. */
function byNavOrder(a, b) {
  const aIdx = a.rel.endsWith('/index.md') ? 0 : 1
  const bIdx = b.rel.endsWith('/index.md') ? 0 : 1
  if (aIdx !== bIdx) return aIdx - bIdx
  return a.rel.localeCompare(b.rel)
}

/** Return the section this page belongs to, or undefined if unmatched. */
function sectionOf(rel) {
  return SECTIONS.find((section) => section.prefix.every((seg, i) => rel.split('/')[i] === seg))
}

async function main() {
  const files = await collectMarkdown(DOCS_DIR)
  const pages = []
  const warnings = []

  for (const file of files) {
    const rel = relative(DOCS_DIR, file).split(sep).join('/')
    if (rel.includes('.vitepress/')) continue

    const content = await readFile(file, 'utf8')
    const fm = parseFrontmatter(content)

    // Skip landing pages (layout: home) — they contain no usable documentation.
    if (fm.layout === 'home') continue

    const title = fm.title || extractTitle(content)
    if (!title) {
      warnings.push(`SKIP ${rel}: no title and no H1 found`)
      continue
    }

    if (!sectionOf(rel)) {
      warnings.push(`WARN ${rel}: page does not match any section and will not be listed`)
    }

    const description = fm.description || extractDescription(content)
    pages.push({ rel, title, description, url: pageUrl(rel) })
  }

  const lines = [
    '# PurePHP',
    '',
    '> PurePHP is a PHP templating engine inspired by ReactJS functional components. It uses PHP objects to represent HTML elements, providing a declarative way to build user interfaces.',
    '',
  ]

  for (const section of SECTIONS) {
    const members = pages
      .filter((p) => sectionOf(p.rel) === section)
      .sort(byNavOrder)
    if (members.length === 0) continue

    lines.push(`## ${section.label}`, '')
    for (const p of members) {
      const desc = p.description ? `: ${p.description}` : ''
      lines.push(`- [${p.title}](${p.url})${desc}`)
    }
    lines.push('')
  }

  const output = lines.join('\n').trimEnd() + '\n'
  await mkdir(dirname(OUTPUT), { recursive: true })
  // UTF-8 BOM is explicitly allowed by the llms.txt spec; it makes browsers
  // decode the file as UTF-8 even when the server omits a charset.
  await writeFile(OUTPUT, '\uFEFF' + output, 'utf8')
  console.log(`Wrote ${OUTPUT} (${output.length} bytes, ${pages.length} pages)`)
  for (const warning of warnings) console.warn(warning)
}

main().catch((err) => {
  console.error(err)
  process.exit(1)
})
