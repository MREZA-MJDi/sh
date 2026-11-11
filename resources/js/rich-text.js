const EDITOR_SELECTOR = 'textarea:not([data-rich-text-ignore])';
const TOOLBAR = [
    { command: 'bold', label: 'B', title: 'ضخیم', className: 'is-bold' },
    { command: 'italic', label: 'I', title: 'ایتالیک', className: 'is-italic' },
    { command: 'underline', label: 'U', title: 'زیرخط', className: 'is-underline' },
    { command: 'strikeThrough', label: 'S', title: 'خط‌خورده', className: 'is-strike' },
    { command: 'justifyRight', label: 'راست‌چین', title: 'راست‌چین' },
    { command: 'justifyCenter', label: 'وسط‌چین', title: 'وسط‌چین' },
    { command: 'justifyLeft', label: 'چپ‌چین', title: 'چپ‌چین' },
    { command: 'insertUnorderedList', label: '• فهرست', title: 'فهرست نشانه‌دار' },
    { command: 'insertOrderedList', label: '۱. فهرست', title: 'فهرست شماره‌دار' },
    { command: 'formatBlock', value: 'blockquote', label: 'نقل‌قول', title: 'نقل‌قول' },
];

const SAFE_TAGS = new Set(['P','BR','DIV','SPAN','STRONG','B','EM','I','U','S','UL','OL','LI','BLOCKQUOTE','H2','H3','H4','PRE','CODE','A','TABLE','THEAD','TBODY','TR','TH','TD','HR']);
const SAFE_STYLES = new Set(['color','background-color','font-family','font-size','font-weight','font-style','text-decoration','text-align']);

function sanitizeInitialHtml(value) {
    const template = document.createElement('template');
    template.innerHTML = value;
    const cleanNode = (parent) => {
        [...parent.childNodes].forEach((node) => {
            if (!(node instanceof Element)) {
                if (node.nodeType !== Node.TEXT_NODE) node.remove();
                return;
            }
            if (!SAFE_TAGS.has(node.tagName)) {
                if (['SCRIPT','STYLE','IFRAME','OBJECT','EMBED','SVG','MATH','FORM','INPUT','BUTTON'].includes(node.tagName)) {
                    node.remove();
                } else {
                    node.replaceWith(...node.childNodes);
                }
                return;
            }
            [...node.attributes].forEach((attribute) => {
                const name = attribute.name.toLowerCase();
                if (name === 'style') {
                    const declarations = [...node.style].filter((property) => {
                        const styleValue = node.style.getPropertyValue(property).trim();
                        return SAFE_STYLES.has(property) && !/url\s*\(|expression\s*\(|[<>]|\\/i.test(styleValue);
                    });
                    const styles = declarations.map((property) => property + ':' + node.style.getPropertyValue(property).trim());
                    if (styles.length) node.setAttribute('style', styles.join(';'));
                    else node.removeAttribute('style');
                } else if (name === 'href' && node.tagName === 'A') {
                    if (!/^(https?:|mailto:|tel:)/i.test(attribute.value.trim())) node.removeAttribute('href');
                } else if (!(name === 'colspan' || name === 'rowspan') || !/^\d{1,2}$/.test(attribute.value)) {
                    node.removeAttribute(attribute.name);
                }
            });
            if (node.tagName === 'A' && node.hasAttribute('href')) {
                node.setAttribute('target','_blank');
                node.setAttribute('rel','nofollow noopener noreferrer');
            }
            cleanNode(node);
        });
    };
    cleanNode(template.content);
    return template.innerHTML;
}

function wrapSelection(style) {
    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0 || selection.isCollapsed) return;
    const span = document.createElement('span');
    Object.assign(span.style, style);
    try {
        const range = selection.getRangeAt(0);
        range.surroundContents(span);
    } catch {
        const range = selection.getRangeAt(0);
        span.append(range.extractContents());
        range.insertNode(span);
    }
    selection.removeAllRanges();
    const range = document.createRange();
    range.selectNodeContents(span);
    selection.addRange(range);
}

function enhanceRichTextarea(textarea) {
    if (textarea.dataset.richReady === 'true' || textarea.disabled || textarea.readOnly) return;
    textarea.dataset.richReady = 'true';

    const shell = document.createElement('div');
    shell.className = 'rich-editor';
    const toolbar = document.createElement('div');
    toolbar.className = 'rich-editor__toolbar';
    toolbar.setAttribute('role', 'toolbar');
    toolbar.setAttribute('aria-label', 'قالب‌بندی متن');

    const editor = document.createElement('div');
    editor.className = 'rich-editor__surface';
    editor.contentEditable = 'true';
    editor.setAttribute('role', 'textbox');
    editor.setAttribute('aria-multiline', 'true');
    editor.setAttribute('dir', 'rtl');
    editor.innerHTML = sanitizeInitialHtml(textarea.value || '');
    editor.dataset.placeholder = textarea.placeholder || 'متن خود را بنویسید…';

    const addButton = (item) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'rich-editor__tool ' + (item.className || '');
        button.textContent = item.label;
        button.title = item.title;
        button.setAttribute('aria-label', item.title);
        button.addEventListener('mousedown', (event) => event.preventDefault());
        button.addEventListener('click', () => {
            editor.focus();
            document.execCommand(item.command, false, item.value || null);
            sync();
        });
        toolbar.append(button);
    };

    const divider = () => {
        const node = document.createElement('span');
        node.className = 'rich-editor__divider';
        toolbar.append(node);
    };

    const font = document.createElement('select');
    font.className = 'rich-editor__select';
    font.setAttribute('aria-label', 'اندازه نوشته');
    [['13px','کوچک'],['15px','معمولی'],['18px','بزرگ'],['22px','خیلی بزرگ'],['28px','عنوان']].forEach(([value,label]) => {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = label;
        font.append(option);
    });
    font.addEventListener('change', () => { editor.focus(); wrapSelection({fontSize:font.value}); sync(); });
    toolbar.append(font);

    const fontFamily = document.createElement('select');
    fontFamily.className = 'rich-editor__select';
    fontFamily.setAttribute('aria-label', 'قلم نوشته');
    [['Vazirmatn','وزیرمتن'],['Tahoma','تاهوما'],['Arial','Arial'],['Georgia','Georgia']].forEach(([value,label]) => {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = label;
        fontFamily.append(option);
    });
    fontFamily.addEventListener('change', () => { editor.focus(); wrapSelection({fontFamily:fontFamily.value}); sync(); });
    toolbar.append(fontFamily);

    const color = document.createElement('label');
    color.className = 'rich-editor__color';
    color.title = 'رنگ متن';
    color.textContent = 'رنگ متن';
    const colorInput = document.createElement('input');
    colorInput.type = 'color';
    colorInput.value = '#24364b';
    colorInput.setAttribute('aria-label', 'رنگ متن');
    colorInput.addEventListener('input', () => { editor.focus(); wrapSelection({color:colorInput.value}); sync(); });
    color.append(colorInput);
    toolbar.append(color);

    const highlight = document.createElement('label');
    highlight.className = 'rich-editor__color';
    highlight.title = 'رنگ پس‌زمینه متن';
    highlight.textContent = 'هایلایت';
    const highlightInput = document.createElement('input');
    highlightInput.type = 'color';
    highlightInput.value = '#fff1a8';
    highlightInput.setAttribute('aria-label', 'رنگ پس‌زمینه متن');
    highlightInput.addEventListener('input', () => { editor.focus(); wrapSelection({backgroundColor:highlightInput.value}); sync(); });
    highlight.append(highlightInput);
    toolbar.append(highlight);

    TOOLBAR.forEach((item, index) => {
        if ([4,7].includes(index)) divider();
        addButton(item);
    });
    divider();

    const linkButton = document.createElement('button');
    linkButton.type = 'button';
    linkButton.className = 'rich-editor__tool';
    linkButton.textContent = 'پیوند';
    linkButton.addEventListener('click', () => {
        const url = window.prompt('نشانی پیوند را وارد کنید (https://)');
        if (!url || !/^https?:\/\//i.test(url.trim())) return;
        editor.focus();
        document.execCommand('createLink', false, url.trim());
        sync();
    });
    toolbar.append(linkButton);

    const tableButton = document.createElement('button');
    tableButton.type = 'button';
    tableButton.className = 'rich-editor__tool';
    tableButton.textContent = 'جدول ۳×۳';
    tableButton.addEventListener('click', () => {
        editor.focus();
        const rows = Array.from({length:3}, () => '<tr><td><br></td><td><br></td><td><br></td></tr>').join('');
        document.execCommand('insertHTML', false, '<table><tbody>' + rows + '</tbody></table><p><br></p>');
        sync();
    });
    toolbar.append(tableButton);

    const clearButton = document.createElement('button');
    clearButton.type = 'button';
    clearButton.className = 'rich-editor__tool';
    clearButton.textContent = 'پاک‌کردن قالب';
    clearButton.addEventListener('click', () => {
        editor.focus();
        document.execCommand('removeFormat');
        sync();
    });
    toolbar.append(clearButton);

    const sync = () => {
        textarea.value = editor.innerHTML;
        textarea.dispatchEvent(new Event('richtext:sync', {bubbles:true}));
    };
    editor.addEventListener('input', sync);
    editor.addEventListener('blur', sync);

    textarea.classList.add('rich-editor__source');
    textarea.insertAdjacentElement('afterend', shell);
    shell.append(toolbar, editor);
    textarea.setAttribute('aria-hidden', 'true');
    textarea.tabIndex = -1;
    textarea.addEventListener('input', () => {
        if (editor.innerHTML !== textarea.value) editor.innerHTML = textarea.value;
    });
    textarea.form?.addEventListener('submit', sync);
    sync();
}

export function initRichTextEditors(root = document) {
    root.querySelectorAll(EDITOR_SELECTOR).forEach(enhanceRichTextarea);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initRichTextEditors(), {once:true});
} else {
    initRichTextEditors();
}

document.addEventListener('admin:drawer-mounted', (event) => {
    initRichTextEditors(event.detail?.root || document);
});
