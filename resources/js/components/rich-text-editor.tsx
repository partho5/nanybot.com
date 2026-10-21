import { CodeBlockLowlight } from '@tiptap/extension-code-block-lowlight';
import { Highlight } from '@tiptap/extension-highlight';
import { Link } from '@tiptap/extension-link';
import { Table } from '@tiptap/extension-table';
import { TableCell } from '@tiptap/extension-table-cell';
import { TableHeader } from '@tiptap/extension-table-header';
import { TableRow } from '@tiptap/extension-table-row';
import { TextStyle, Color, FontSize } from '@tiptap/extension-text-style';
import { Underline } from '@tiptap/extension-underline';
import { NodeSelection } from '@tiptap/pm/state';
import { EditorContent, useEditor, useEditorState } from '@tiptap/react';
import type { Editor } from '@tiptap/react';
import { BubbleMenu } from '@tiptap/react/menus';
import StarterKit from '@tiptap/starter-kit';
import { common, createLowlight } from 'lowlight';
import { useCallback, useRef, useState } from 'react';
import {
    CALLOUT_VARIANTS,
    SECTION_VARIANTS,
} from '@/components/editor/block-palette';
import type {
    CalloutVariantKey,
    SectionVariantKey,
} from '@/components/editor/block-palette';
import ImageDialog from '@/components/editor/image-dialog';
import type { ImageValues } from '@/components/editor/image-dialog';
import { Callout } from '@/extensions/callout-extension';
import { CaptionedImage } from '@/extensions/image-extension';
import { SectionBlock as SectionBlockExt } from '@/extensions/section-block-extension';
import { Youtube, extractYoutubeId } from '@/extensions/youtube-extension';

const lowlight = createLowlight(common);

// ─── Toolbar button ───────────────────────────────────────────────────────────

function ToolbarBtn({
    onClick,
    active = false,
    title,
    children,
}: {
    onClick: () => void;
    active?: boolean;
    title: string;
    children: React.ReactNode;
}) {
    return (
        <button
            type="button"
            onMouseDown={(e) => {
                e.preventDefault();
                onClick();
            }}
            title={title}
            className={`rounded px-1.5 py-0.5 text-xs font-medium transition-colors ${
                active
                    ? 'bg-primary text-primary-foreground'
                    : 'text-foreground hover:bg-muted'
            }`}
        >
            {children}
        </button>
    );
}

function Sep() {
    return (
        <div className="mx-1 h-5 w-0.5 rounded-full bg-black/40 dark:bg-white/40" />
    );
}

// ─── Toolbar ──────────────────────────────────────────────────────────────────

const FONT_SIZES = [
    '12px',
    '14px',
    '16px',
    '18px',
    '20px',
    '24px',
    '28px',
    '32px',
];
const TEXT_COLORS = [
    '#000000',
    '#ef4444',
    '#f97316',
    '#f59e0b',
    '#ffffff',
    '#3b82f6',
    '#8b5cf6',
    '#ec4899',
    '#06b6d4',
    '#6b7280',
];
const HIGHLIGHT_COLORS = [
    '#ffff00',
    '#ff0000',
    '#00ff00',
    '#00ffff',
    '#ff8c00',
    '#ff69b4',
];
type YtPanel = {
    open: boolean;
    input: string;
    videoId: string;
    error: string | null;
};
const YT_PANEL_CLOSED: YtPanel = {
    open: false,
    input: '',
    videoId: '',
    error: null,
};

const PANEL_CLASSES =
    'border-b border-primary/40 border-l-4 border-l-primary bg-primary/5 dark:bg-primary/10 px-3 py-2 flex flex-wrap items-end gap-3 animate-in fade-in slide-in-from-top-1 duration-200';

function Toolbar({
    editor,
    sticky = false,
    onImage,
}: {
    editor: Editor;
    sticky?: boolean;
    /** Opens the image dialog: edits the selected image, or inserts a new one. */
    onImage: () => void;
}) {
    const colorRef = useRef<HTMLInputElement>(null);
    const highlightRef = useRef<HTMLInputElement>(null);
    const [ytPanel, setYtPanel] = useState<YtPanel>(YT_PANEL_CLOSED);

    const openYtPanel = useCallback(() => {
        setYtPanel((p) =>
            p.open ? YT_PANEL_CLOSED : { ...YT_PANEL_CLOSED, open: true },
        );
    }, []);

    const s = useEditorState({
        editor,
        selector: (ctx) => ({
            isBold: ctx.editor.isActive('bold'),
            isItalic: ctx.editor.isActive('italic'),
            isUnderline: ctx.editor.isActive('underline'),
            isStrike: ctx.editor.isActive('strike'),
            isLink: ctx.editor.isActive('link'),
            isImage: ctx.editor.isActive('image'),
            isCodeBlock: ctx.editor.isActive('codeBlock'),
            isBulletList: ctx.editor.isActive('bulletList'),
            isOrderedList: ctx.editor.isActive('orderedList'),
            isHighlight: ctx.editor.isActive('highlight'),
            isTable: ctx.editor.isActive('table'),
            headingLevel: ctx.editor.isActive('heading', { level: 1 })
                ? '1'
                : ctx.editor.isActive('heading', { level: 2 })
                  ? '2'
                  : ctx.editor.isActive('heading', { level: 3 })
                    ? '3'
                    : '',
            currentColor: ctx.editor.getAttributes('textStyle').color as
                string | undefined,
            currentHighlight: ctx.editor.getAttributes('highlight').color as
                string | undefined,
        }),
    });

    const setLink = useCallback(() => {
        const prev = editor.getAttributes('link').href as string | undefined;
        const url = window.prompt('URL', prev ?? '');
        if (url === null) {
            return;
        }
        if (url === '') {
            editor.chain().focus().extendMarkRange('link').unsetLink().run();
        } else {
            editor
                .chain()
                .focus()
                .extendMarkRange('link')
                .setLink({ href: url })
                .run();
        }
    }, [editor]);

    const handleYtInput = useCallback((value: string) => {
        setYtPanel((p) => ({
            ...p,
            input: value,
            videoId: extractYoutubeId(value) ?? '',
            error: null,
        }));
    }, []);

    const insertYoutube = useCallback(() => {
        const id = ytPanel.videoId || extractYoutubeId(ytPanel.input);
        if (!id) {
            setYtPanel((p) => ({
                ...p,
                error: 'Could not recognize a YouTube URL or video ID.',
            }));
            return;
        }
        editor.chain().focus().insertYoutube(id).run();
        setYtPanel(YT_PANEL_CLOSED);
    }, [editor, ytPanel]);

    return (
        <>
            <div
                className={`flex flex-wrap items-center gap-0.5 rounded-t-md border-b border-border bg-muted px-2 py-1.5 ${sticky ? 'sticky top-0 z-10' : ''}`}
            >
                {/* Heading */}
                <select
                    className="h-6 cursor-pointer rounded border-0 bg-transparent text-xs focus:outline-none"
                    value={s.headingLevel}
                    onChange={(e) => {
                        const level = Number(e.target.value);
                        if (!level) {
                            editor.chain().focus().setParagraph().run();
                        } else {
                            editor
                                .chain()
                                .focus()
                                .toggleHeading({ level: level as 1 | 2 | 3 })
                                .run();
                        }
                    }}
                >
                    <option value="">Paragraph</option>
                    <option value="1">Heading 1</option>
                    <option value="2">Heading 2</option>
                    <option value="3">Heading 3</option>
                </select>

                <Sep />

                {/* Font size */}
                <select
                    className="h-6 cursor-pointer rounded border-0 bg-transparent text-xs focus:outline-none"
                    defaultValue=""
                    onChange={(e) => {
                        const size = e.target.value;
                        if (size) {
                            (editor.chain().focus() as any)
                                .setFontSize(size)
                                .run();
                        } else {
                            (editor.chain().focus() as any)
                                .unsetFontSize()
                                .run();
                        }
                    }}
                >
                    <option value="">Size</option>
                    {FONT_SIZES.map((sz) => (
                        <option key={sz} value={sz}>
                            {sz}
                        </option>
                    ))}
                </select>

                <Sep />

                {/* Basic formatting */}
                <ToolbarBtn
                    onClick={() => editor.chain().focus().toggleBold().run()}
                    active={s.isBold}
                    title="Bold"
                >
                    <b>B</b>
                </ToolbarBtn>
                <ToolbarBtn
                    onClick={() => editor.chain().focus().toggleItalic().run()}
                    active={s.isItalic}
                    title="Italic"
                >
                    <i>I</i>
                </ToolbarBtn>
                <ToolbarBtn
                    onClick={() =>
                        editor.chain().focus().toggleUnderline().run()
                    }
                    active={s.isUnderline}
                    title="Underline"
                >
                    <u>U</u>
                </ToolbarBtn>
                <ToolbarBtn
                    onClick={() => editor.chain().focus().toggleStrike().run()}
                    active={s.isStrike}
                    title="Strikethrough"
                >
                    <s>S</s>
                </ToolbarBtn>

                <Sep />

                {/* Text color */}
                <input
                    ref={colorRef}
                    type="color"
                    className="sr-only"
                    defaultValue="#000000"
                    onChange={(e) =>
                        editor.chain().focus().setColor(e.target.value).run()
                    }
                />
                <ToolbarBtn
                    onClick={() => colorRef.current?.click()}
                    active={!!s.currentColor}
                    title="Text color"
                >
                    <span className="flex flex-col items-center leading-none">
                        <span className="text-sm font-bold">A</span>
                        <span
                            className="block h-0.5 w-3 rounded"
                            style={{
                                backgroundColor: s.currentColor ?? '#000',
                            }}
                        />
                    </span>
                </ToolbarBtn>
                <div className="flex items-center gap-0.5">
                    {TEXT_COLORS.map((c) => (
                        <button
                            key={c}
                            type="button"
                            onMouseDown={(e) => {
                                e.preventDefault();
                                editor.chain().focus().setColor(c).run();
                            }}
                            title={c}
                            className="h-4 w-4 rounded border border-border shadow-sm"
                            style={{ backgroundColor: c }}
                        />
                    ))}
                    <ToolbarBtn
                        onClick={() =>
                            editor.chain().focus().unsetColor().run()
                        }
                        title="Remove color"
                    >
                        ✕
                    </ToolbarBtn>
                </div>

                <Sep />

                {/* Highlight */}
                <input
                    ref={highlightRef}
                    type="color"
                    className="sr-only"
                    defaultValue="#fef08a"
                    onChange={(e) =>
                        editor
                            .chain()
                            .focus()
                            .setHighlight({ color: e.target.value })
                            .run()
                    }
                />
                <ToolbarBtn
                    onClick={() => highlightRef.current?.click()}
                    active={s.isHighlight}
                    title="Highlight"
                >
                    <span className="flex flex-col items-center leading-none">
                        <span className="text-sm">H</span>
                        <span
                            className="block h-0.5 w-3 rounded"
                            style={{
                                backgroundColor:
                                    s.currentHighlight ?? '#fef08a',
                            }}
                        />
                    </span>
                </ToolbarBtn>
                <div className="flex items-center gap-0.5">
                    {HIGHLIGHT_COLORS.map((c) => (
                        <button
                            key={c}
                            type="button"
                            onMouseDown={(e) => {
                                e.preventDefault();
                                editor
                                    .chain()
                                    .focus()
                                    .setHighlight({ color: c })
                                    .run();
                            }}
                            title={c}
                            className="h-4 w-4 rounded border border-border shadow-sm"
                            style={{ backgroundColor: c }}
                        />
                    ))}
                    <ToolbarBtn
                        onClick={() =>
                            editor.chain().focus().unsetHighlight().run()
                        }
                        title="Remove highlight"
                    >
                        ✕
                    </ToolbarBtn>
                </div>

                <Sep />

                {/* Lists */}
                <ToolbarBtn
                    onClick={() =>
                        editor.chain().focus().toggleBulletList().run()
                    }
                    active={s.isBulletList}
                    title="Bullet list"
                >
                    ≡
                </ToolbarBtn>
                <ToolbarBtn
                    onClick={() =>
                        editor.chain().focus().toggleOrderedList().run()
                    }
                    active={s.isOrderedList}
                    title="Ordered list"
                >
                    #≡
                </ToolbarBtn>

                <Sep />

                {/* Link, image, video & code */}
                <ToolbarBtn onClick={setLink} active={s.isLink} title="Link">
                    🔗
                </ToolbarBtn>
                <ToolbarBtn
                    onClick={onImage}
                    active={s.isImage}
                    title={s.isImage ? 'Edit image' : 'Insert image'}
                >
                    🖼
                </ToolbarBtn>
                <ToolbarBtn
                    onClick={openYtPanel}
                    active={ytPanel.open}
                    title="Insert YouTube video"
                >
                    ▶
                </ToolbarBtn>
                <ToolbarBtn
                    onClick={() =>
                        editor.chain().focus().toggleCodeBlock().run()
                    }
                    active={s.isCodeBlock}
                    title="Code block"
                >{`</>`}</ToolbarBtn>

                <Sep />

                {/* Table */}
                <ToolbarBtn
                    onClick={() =>
                        (editor.chain().focus() as any)
                            .insertTable({
                                rows: 3,
                                cols: 3,
                                withHeaderRow: true,
                            })
                            .run()
                    }
                    active={s.isTable}
                    title="Insert table"
                >
                    ⊞
                </ToolbarBtn>
                {s.isTable && (
                    <>
                        <ToolbarBtn
                            onClick={() =>
                                (editor.chain().focus() as any)
                                    .addColumnAfter()
                                    .run()
                            }
                            title="Add column"
                        >
                            +C
                        </ToolbarBtn>
                        <ToolbarBtn
                            onClick={() =>
                                (editor.chain().focus() as any)
                                    .deleteColumn()
                                    .run()
                            }
                            title="Delete column"
                        >
                            −C
                        </ToolbarBtn>
                        <ToolbarBtn
                            onClick={() =>
                                (editor.chain().focus() as any)
                                    .addRowAfter()
                                    .run()
                            }
                            title="Add row"
                        >
                            +R
                        </ToolbarBtn>
                        <ToolbarBtn
                            onClick={() =>
                                (editor.chain().focus() as any)
                                    .deleteRow()
                                    .run()
                            }
                            title="Delete row"
                        >
                            −R
                        </ToolbarBtn>
                        <ToolbarBtn
                            onClick={() =>
                                (editor.chain().focus() as any)
                                    .deleteTable()
                                    .run()
                            }
                            title="Delete table"
                        >
                            ✕T
                        </ToolbarBtn>
                    </>
                )}

                <Sep />

                {/* Callout */}
                <div className="flex items-center gap-0.5">
                    {(Object.keys(CALLOUT_VARIANTS) as CalloutVariantKey[]).map(
                        (v) => {
                            const { label, bg, rule } = CALLOUT_VARIANTS[v];
                            return (
                                <button
                                    key={v}
                                    type="button"
                                    onMouseDown={(e) => {
                                        e.preventDefault();
                                        editor
                                            .chain()
                                            .focus()
                                            .insertCallout(v)
                                            .run();
                                    }}
                                    title={`Insert ${label} callout`}
                                    aria-label={`Insert ${label} callout`}
                                    className="h-4 w-4 border border-border shadow-sm"
                                    style={{
                                        background: bg,
                                        borderLeft: `3px solid ${rule}`,
                                    }}
                                />
                            );
                        },
                    )}
                </div>

                <Sep />

                {/* Section block */}
                <div className="flex items-center gap-0.5">
                    <span className="mr-0.5 text-[10px] text-muted-foreground">
                        Section:
                    </span>
                    {(Object.keys(SECTION_VARIANTS) as SectionVariantKey[]).map(
                        (v) => {
                            const { label, bg, border, topRule } =
                                SECTION_VARIANTS[v];
                            return (
                                <button
                                    key={v}
                                    type="button"
                                    onMouseDown={(e) => {
                                        e.preventDefault();
                                        editor
                                            .chain()
                                            .focus()
                                            .insertSection(v)
                                            .run();
                                    }}
                                    title={`Insert ${label} section`}
                                    aria-label={`Insert ${label} section`}
                                    className="h-4 w-4 border border-border shadow-sm"
                                    style={{
                                        background: bg,
                                        ...(border !== 'none'
                                            ? { border }
                                            : {}),
                                        ...(topRule
                                            ? {
                                                  borderTop:
                                                      '3px solid var(--blk-brand)',
                                              }
                                            : {}),
                                    }}
                                />
                            );
                        },
                    )}
                </div>
            </div>

            {/* YouTube insertion panel */}
            {ytPanel.open && (
                <div className={PANEL_CLASSES}>
                    {/* URL / ID input */}
                    <div className="flex min-w-[240px] flex-1 flex-col gap-0.5">
                        <span className="text-[10px] text-muted-foreground">
                            YouTube URL or video ID
                        </span>
                        <input
                            type="text"
                            placeholder="https://www.youtube.com/watch?v=... or dQw4w9WgXcQ"
                            value={ytPanel.input}
                            autoFocus
                            onChange={(e) => handleYtInput(e.target.value)}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') {
                                    e.preventDefault();
                                    insertYoutube();
                                }
                            }}
                            className="h-6 rounded border border-border bg-background px-2 text-xs focus:ring-1 focus:ring-ring focus:outline-none"
                        />
                        {ytPanel.error && (
                            <span className="text-[10px] text-destructive">
                                {ytPanel.error}
                            </span>
                        )}
                        {ytPanel.videoId && !ytPanel.error && (
                            <span className="text-[10px] text-muted-foreground">
                                Detected ID: {ytPanel.videoId}
                            </span>
                        )}
                    </div>

                    {/* Thumbnail preview */}
                    {ytPanel.videoId && (
                        <img
                            src={`https://i.ytimg.com/vi/${ytPanel.videoId}/mqdefault.jpg`}
                            alt="YouTube thumbnail"
                            className="h-12 w-[72px] rounded border border-border object-cover shadow-sm"
                            onError={(e) => {
                                (e.target as HTMLImageElement).style.display =
                                    'none';
                            }}
                        />
                    )}

                    {/* Actions */}
                    <div className="flex items-end gap-1.5 self-end">
                        <button
                            type="button"
                            onMouseDown={(e) => {
                                e.preventDefault();
                                insertYoutube();
                            }}
                            disabled={!ytPanel.videoId}
                            className="rounded bg-primary px-3 py-0.5 text-xs text-primary-foreground transition-opacity disabled:opacity-40"
                        >
                            Insert
                        </button>
                        <button
                            type="button"
                            onMouseDown={(e) => {
                                e.preventDefault();
                                setYtPanel(YT_PANEL_CLOSED);
                            }}
                            className="rounded border border-border px-2 py-0.5 text-xs transition-colors hover:bg-muted"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            )}
        </>
    );
}

// ─── RichTextEditor ───────────────────────────────────────────────────────────

interface RichTextEditorProps {
    value: string;
    onChange: (content: string) => void;
    disabled?: boolean;
    placeholder?: string;
    autoFocus?: boolean;
    /** Writing-canvas mode: tall, grows with the content, toolbar stays in view. */
    tall?: boolean;
    /** Called with every image URL uploaded through the editor (to clean up unused ones on save). */
    onImageUploaded?: (url: string) => void;
}

function toImageValues(attrs: Record<string, unknown>): ImageValues | null {
    if (typeof attrs.src !== 'string' || !attrs.src) {
        return null;
    }

    return {
        src: attrs.src,
        alt: typeof attrs.alt === 'string' ? attrs.alt : '',
        caption: typeof attrs.caption === 'string' ? attrs.caption : '',
        width: typeof attrs.width === 'number' ? attrs.width : null,
        height: typeof attrs.height === 'number' ? attrs.height : null,
    };
}

export default function RichTextEditor({
    value,
    onChange,
    disabled = false,
    placeholder,
    autoFocus = false,
    tall = false,
    onImageUploaded,
}: RichTextEditorProps) {
    // null = closed; 'insert' adds a new image, 'edit' changes the selected one.
    // The image's values are captured when the dialog opens, so edit mode never
    // depends on the editor selection still being intact at render time.
    const [imageDialog, setImageDialog] = useState<{
        mode: 'insert' | 'edit';
        initial: ImageValues | null;
    } | null>(null);

    const editor = useEditor({
        autofocus: autoFocus ? 'end' : false,
        extensions: [
            StarterKit.configure({ codeBlock: false }),
            CodeBlockLowlight.configure({
                lowlight,
                defaultLanguage: null,
                HTMLAttributes: { class: 'hljs' },
            }),
            Underline,
            TextStyle,
            FontSize,
            Color,
            Highlight.configure({ multicolor: true }),
            Link.configure({ openOnClick: false }),
            CaptionedImage.configure({ inline: false, allowBase64: false }),
            Table.configure({ resizable: true }),
            TableRow,
            TableHeader,
            TableCell,
            Callout,
            SectionBlockExt,
            Youtube,
        ],
        content: value,
        editable: !disabled,
        editorProps: {
            // Double-click an image to edit its alt text, caption or source.
            handleDoubleClickOn: (view, _pos, node, nodePos) => {
                if (node.type.name !== 'image') {
                    return false;
                }

                view.dispatch(
                    view.state.tr.setSelection(
                        NodeSelection.create(view.state.doc, nodePos),
                    ),
                );
                setImageDialog({
                    mode: 'edit',
                    initial: toImageValues(node.attrs),
                });

                return true;
            },
        },
        onUpdate: ({ editor }) => {
            onChange(editor.getHTML());
        },
    });

    if (!editor) {
        return null;
    }

    function openImage() {
        setImageDialog(
            editor?.isActive('image')
                ? {
                      mode: 'edit',
                      initial: toImageValues(editor.getAttributes('image')),
                  }
                : { mode: 'insert', initial: null },
        );
    }

    function closeImage() {
        setImageDialog(null);
        editor?.commands.focus();
    }

    function applyImage(values: ImageValues) {
        const attrs = {
            src: values.src,
            alt: values.alt || null,
            caption: values.caption || null,
            width: values.width,
            height: values.height,
        };

        if (imageDialog?.mode === 'edit') {
            editor?.chain().focus().updateAttributes('image', attrs).run();
        } else {
            editor
                ?.chain()
                .focus()
                .setImage({
                    ...attrs,
                    alt: attrs.alt ?? undefined,
                    width: attrs.width ?? undefined,
                    height: attrs.height ?? undefined,
                } as Parameters<
                    NonNullable<typeof editor>['commands']['setImage']
                >[0])
                .run();
        }

        setImageDialog(null);
    }

    function removeImage() {
        editor?.chain().focus().deleteSelection().run();
        setImageDialog(null);
    }

    return (
        <div
            className={`tiptap-editor rounded-md border border-input bg-card text-sm ${disabled ? 'pointer-events-none opacity-50' : ''}`}
        >
            <Toolbar editor={editor} sticky={tall} onImage={openImage} />
            <BubbleMenu
                editor={editor}
                shouldShow={({ editor: e }) =>
                    e.isEditable && e.isActive('image')
                }
                className="flex items-center gap-1 rounded-md border bg-popover p-1 text-xs shadow-md"
            >
                <button
                    type="button"
                    onMouseDown={(e) => {
                        e.preventDefault();
                        setImageDialog({
                            mode: 'edit',
                            initial: toImageValues(
                                editor.getAttributes('image'),
                            ),
                        });
                    }}
                    className="rounded px-2 py-1 hover:bg-muted"
                >
                    Edit image
                </button>
                <button
                    type="button"
                    onMouseDown={(e) => {
                        e.preventDefault();
                        editor.chain().focus().deleteSelection().run();
                    }}
                    className="rounded px-2 py-1 text-destructive hover:bg-muted"
                >
                    Remove
                </button>
            </BubbleMenu>
            <div
                className={`rounded-b-md focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-1 ${tall ? 'min-h-[32rem] lg:min-h-[calc(100svh-14rem)] [&_.ProseMirror]:min-h-[inherit]!' : 'max-h-[60vh] min-h-48 overflow-y-auto'}`}
            >
                <EditorContent editor={editor} placeholder={placeholder} />
            </div>
            {imageDialog && (
                <ImageDialog
                    initial={imageDialog.initial}
                    onSubmit={applyImage}
                    onRemove={removeImage}
                    onClose={closeImage}
                    onUploaded={onImageUploaded}
                />
            )}
        </div>
    );
}
