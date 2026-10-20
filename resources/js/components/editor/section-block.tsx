import { NodeViewWrapper, NodeViewContent } from '@tiptap/react';
import type { NodeViewProps } from '@tiptap/react';

import { BLOCK_MONO, SECTION_VARIANTS } from './block-palette';
import type { SectionVariantKey as VariantKey } from './block-palette';

export default function SectionBlock({
    node,
    updateAttributes,
}: NodeViewProps) {
    const variant = (node.attrs.variant as VariantKey) || 'hero-dark';
    const label = (node.attrs.label as string) || '';
    const colors = SECTION_VARIANTS[variant] ?? SECTION_VARIANTS['hero-dark'];

    return (
        <NodeViewWrapper
            style={{
                background: colors.bg,
                border: colors.border,
                borderTop: colors.topRule
                    ? '3px solid var(--blk-brand)'
                    : colors.border,
                borderRadius: 0,
                boxShadow: colors.edge,
                padding: '20px 24px',
                margin: '16px 0',
                color: colors.text,
            }}
        >
            {/* Controls row */}
            <div
                contentEditable={false}
                style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '8px',
                    marginBottom: '12px',
                    userSelect: 'none',
                }}
            >
                {/* Label input */}
                <input
                    type="text"
                    value={label}
                    placeholder="SECTION LABEL..."
                    aria-label="Section label"
                    onChange={(e) =>
                        updateAttributes({ label: e.target.value })
                    }
                    style={{
                        background: 'transparent',
                        border: 'none',
                        outline: 'none',
                        fontSize: 11,
                        fontWeight: 600,
                        letterSpacing: '0.08em',
                        textTransform: 'uppercase',
                        fontFamily: BLOCK_MONO,
                        color: colors.accent,
                        flex: 1,
                        padding: 0,
                        margin: 0,
                        lineHeight: '1.4',
                    }}
                />

                {/* Variant switcher */}
                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: '4px',
                        flexShrink: 0,
                    }}
                >
                    {(Object.keys(SECTION_VARIANTS) as VariantKey[]).map(
                        (key) => {
                            const v = SECTION_VARIANTS[key];
                            const isActive = key === variant;
                            return (
                                <button
                                    key={key}
                                    type="button"
                                    onMouseDown={(e) => e.preventDefault()}
                                    onClick={() =>
                                        updateAttributes({ variant: key })
                                    }
                                    title={`${v.label} section`}
                                    aria-label={`${v.label} section`}
                                    aria-pressed={isActive}
                                    style={{
                                        width: 18,
                                        height: 18,
                                        borderRadius: 0,
                                        background: v.bg,
                                        border:
                                            v.border === 'none'
                                                ? '1px solid var(--blk-line)'
                                                : v.border,
                                        borderTop: v.topRule
                                            ? '3px solid var(--blk-brand)'
                                            : undefined,
                                        outline: isActive
                                            ? '2px solid var(--blk-brand)'
                                            : 'none',
                                        outlineOffset: '1px',
                                        cursor: 'pointer',
                                        padding: 0,
                                    }}
                                />
                            );
                        },
                    )}
                </div>
            </div>

            {/* Editable block content — supports headings, lists, bold, etc. */}
            <NodeViewContent
                className="section-block-content"
                style={{ outline: 'none', color: colors.text }}
            />
        </NodeViewWrapper>
    );
}
