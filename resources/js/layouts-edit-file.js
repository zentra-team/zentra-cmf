const cfg = window.ZentraConfig || {};

const editor = ace.edit('aceFileEditor');
editor.setTheme('ace/theme/monokai');
editor.session.setMode('ace/mode/' + (cfg.fileType === 'js' ? 'javascript' : cfg.fileType));
editor.setOptions({
    fontSize: '13px',
    showPrintMargin: false,
    wrap: false,
    tabSize: 2,
    useSoftTabs: true,
});
editor.setValue(cfg.content, -1);

if (cfg.canFiles) {
    document.getElementById('btnSave').addEventListener('click', () => {
        document.getElementById('hiddenContent').value = editor.getValue();
        document.getElementById('saveForm').submit();
    });

    editor.commands.addCommand({
        name: 'save',
        bindKey: { win: 'Ctrl-S', mac: 'Command-S' },
        exec: () => {
            document.getElementById('btnSave').click();
        },
    });

    const btnFormat = document.getElementById('btnFormat');
    if (btnFormat) {
        btnFormat.addEventListener('click', () => {
            ace.require('ace/ext/beautify').beautify(editor.session);
        });
    }
} else {
    editor.setReadOnly(true);
}
