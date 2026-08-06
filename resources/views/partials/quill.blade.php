@once
    @push('head')
        <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
        <style>
            .rich-editor-holder { background: #fff; }
            .rich-editor-holder .ql-editor { min-height: 120px; max-height: 260px; overflow-y: auto; font-size: .95rem; font-family: inherit; line-height: 1.7; }
            .rich-editor-holder .ql-editor.ql-blank::before { font-style: normal; color: #9a9a9a; }
            .ql-toolbar.ql-snow, .ql-container.ql-snow { border-color: #d8d2d2; }
            .ql-toolbar.ql-snow { border-top-left-radius: 8px; border-top-right-radius: 8px; }
            .ql-container.ql-snow { border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; }
            .ql-snow .ql-stroke { stroke: #5f605f; }
            .ql-snow .ql-fill { fill: #5f605f; }
            .ql-snow.ql-toolbar button:hover .ql-stroke,
            .ql-snow.ql-toolbar button.ql-active .ql-stroke { stroke: var(--brand); }
            .ql-snow.ql-toolbar button:hover .ql-fill,
            .ql-snow.ql-toolbar button.ql-active .ql-fill { fill: var(--brand); }
            .ql-snow .ql-picker.ql-expanded .ql-picker-label { border-color: var(--brand); }
            textarea.rich-hidden { position: absolute !important; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; opacity: 0; pointer-events: none; }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
        <script>
        (function () {
            var TOOLBAR = [
                [{ header: [2, 3, false] }],
                ['bold', 'italic', 'underline'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link', 'blockquote'],
                ['clean']
            ];

            function initOne(ta) {
                if (!window.Quill || ta.dataset.quillReady) return;
                ta.dataset.quillReady = '1';

                var holder = document.createElement('div');
                holder.className = 'rich-editor-holder';
                ta.parentNode.insertBefore(holder, ta.nextSibling);
                ta.classList.add('rich-hidden');
                ta.setAttribute('aria-hidden', 'true');
                ta.setAttribute('tabindex', '-1');
                // A hidden `required` control is not focusable and blocks submit — server-side validation still enforces it.
                ta.removeAttribute('required');

                var quill = new Quill(holder, {
                    theme: 'snow',
                    placeholder: ta.getAttribute('placeholder') || 'Write here…',
                    modules: { toolbar: TOOLBAR }
                });

                var initial = (ta.value || '').trim();
                if (initial) {
                    // Convert legacy plain text (no tags) into paragraphs.
                    if (initial.indexOf('<') === -1) {
                        initial = initial.split(/\n{2,}/).map(function (p) {
                            return '<p>' + p.replace(/\n/g, '<br>') + '</p>';
                        }).join('');
                    }
                    quill.clipboard.dangerouslyPasteHTML(initial);
                }

                function sync() {
                    ta.value = quill.getText().trim().length ? quill.root.innerHTML : '';
                }
                quill.on('text-change', sync);
                var form = ta.closest('form');
                if (form) form.addEventListener('submit', sync);
            }

            function scan(root) {
                (root || document).querySelectorAll('textarea[data-rich]').forEach(initOne);
            }

            function start() {
                scan();
                // Enhance textareas injected later (e.g. admin edit modal loaded via AJAX).
                new MutationObserver(function (mutations) {
                    mutations.forEach(function (m) {
                        m.addedNodes.forEach(function (n) {
                            if (n.nodeType !== 1) return;
                            if (n.matches && n.matches('textarea[data-rich]')) initOne(n);
                            else if (n.querySelectorAll) scan(n);
                        });
                    });
                }).observe(document.body, { childList: true, subtree: true });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', start);
            } else {
                start();
            }
        })();
        </script>
    @endpush
@endonce
