/**
 * Callout + section block palette: the neutral + red-accent colours the public blog uses
 * (resources/css/blog.css). Stored variant names are kept so existing content
 * needs no migration; only labels and colours changed. Colours are the
 * --blk-* vars from resources/css/app.css, which also switch for dark mode.
 */
export const CALLOUT_VARIANTS = {
    purple: {
        label: 'Note',
        rule: 'var(--blk-ink)',
        bg: 'var(--blk-soft)',
        outline: false,
    },
    amber: {
        label: 'Red',
        rule: 'var(--blk-brand)',
        bg: 'var(--blk-brand-tint)',
        outline: false,
    },
    teal: {
        label: 'Outline',
        rule: 'var(--blk-ink)',
        bg: 'transparent',
        outline: true,
    },
    green: {
        label: 'Tip',
        rule: 'var(--blk-ok)',
        bg: 'var(--blk-soft)',
        outline: false,
    },
} as const;

export type CalloutVariantKey = keyof typeof CALLOUT_VARIANTS;

export const SECTION_VARIANTS = {
    'hero-dark': {
        label: 'Ink',
        bg: 'var(--blk-panel)',
        text: 'var(--blk-panel-fg)',
        border: 'none',
        edge: 'inset 0 0 0 1px var(--blk-panel-edge)',
        topRule: false,
        accent: 'color-mix(in srgb, var(--blk-panel-fg) 70%, transparent)',
    },
    'hero-light': {
        label: 'Ink + red',
        bg: 'var(--blk-panel)',
        text: 'var(--blk-panel-fg)',
        border: 'none',
        edge: 'inset 0 0 0 1px var(--blk-panel-edge)',
        topRule: true,
        accent: 'color-mix(in srgb, var(--blk-panel-fg) 70%, transparent)',
    },
    accent: {
        label: 'Soft',
        bg: 'var(--blk-soft)',
        text: 'var(--blk-ink)',
        border: 'none',
        edge: 'none',
        topRule: false,
        accent: 'var(--blk-muted)',
    },
    bordered: {
        label: 'Outline',
        bg: 'transparent',
        text: 'var(--blk-ink)',
        border: '1px solid var(--blk-ink)',
        edge: 'none',
        topRule: false,
        accent: 'var(--blk-muted)',
    },
} as const;

export type SectionVariantKey = keyof typeof SECTION_VARIANTS;

export const BLOCK_MONO =
    "'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace";
