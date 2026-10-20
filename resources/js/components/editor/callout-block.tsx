import { NodeViewWrapper, NodeViewContent } from '@tiptap/react';
import type { NodeViewProps } from '@tiptap/react';

import { BLOCK_MONO as MONO, CALLOUT_VARIANTS } from './block-palette';
import type { CalloutVariantKey as VariantKey } from './block-palette';

export default function CalloutBlock({
    node,
    updateAttributes,
}: NodeViewProps) {
    const variant = (node.attrs.variant as VariantKey) || 'purple';
    const label = (node.attrs.label as string) || '';
    const colors = CALLOUT_VARIANTS[variant] ?? CALLOUT_VARIANTS.purple;
    const isEmpty = node.content.size === 0;

    return (
        <NodeViewWrapper
            style={{
                border: colors.outline ? '1px solid var(--blk-line)' : 'none',
                borderLeft: `3px solid ${colors.rule}`,
                borderRadius: 0,
                background: colors.bg,
                color: 'var(--blk-ink)',
                padding: '12px 16px',
                margin: '8px 0',
            }}
        >
            {/* Label row */}
            <div
                contentEditable={false}
                style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '6px',
                    marginBottom: '6px',
                    userSelect: 'none',
                }}
            >
                {/* Editable label */}
                <input
                    type="text"
                    value={label}
                    placeholder="LABEL..."
                    aria-label="Callout label"
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
                        color: colors.rule,
                        flex: 1,
                        padding: 0,
                        margin: 0,
                        lineHeight: '1.4',
                        fontFamily: MONO,
                    }}
                />

                {/* Variant switcher: square swatches showing fill + rule */}
                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: '4px',
                        flexShrink: 0,
                    }}
                >
                    {(Object.keys(CALLOUT_VARIANTS) as VariantKey[]).map(
                        (key) => {
                            const v = CALLOUT_VARIANTS[key];
                            const isActive = key === variant;
                            return (
                                <button
                                    key={key}
                                    type="button"
                                    onMouseDown={(e) => e.preventDefault()}
                                    onClick={() =>
                                        updateAttributes({ variant: key })
                                    }
                                    title={`${v.label} callout`}
                                    aria-label={`${v.label} callout`}
                                    aria-pressed={isActive}
                                    style={{
                                        width: 16,
                                        height: 16,
                                        borderRadius: 0,
                                        background: v.bg,
                                        border: '1px solid var(--blk-line)',
                                        borderLeft: `3px solid ${v.rule}`,
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

            {/* Body content */}
            <div style={{ position: 'relative' }}>
                {isEmpty && (
                    <div
                        style={{
                            position: 'absolute',
                            top: 0,
                            left: 0,
                            opacity: 0.45,
                            pointerEvents: 'none',
                            lineHeight: '1.65',
                        }}
                    >
                        Write here...
                    </div>
                )}
                <NodeViewContent
                    style={{
                        lineHeight: '1.65',
                        color: 'var(--blk-ink)',
                        outline: 'none',
                    }}
                />
            </div>
        </NodeViewWrapper>
    );
}
