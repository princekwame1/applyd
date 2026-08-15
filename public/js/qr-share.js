/*
 * Draws a QR code onto a <canvas>, using the local qrcode-generator library
 * (public/js/qrcode.js, MIT).
 *
 * This used to be a CDN script, which is exactly why the codes stopped
 * appearing: one blocked or unreachable request and the panel renders empty.
 * Both files are served from our own origin now, so a QR is either drawn or
 * the caller is told it failed — never silently missing.
 */
window.ApplydQr = (function () {
    'use strict';

    /**
     * @param  {HTMLCanvasElement} canvas
     * @param  {string} value      what the code encodes — usually a full URL
     * @param  {number} [size]     drawn size in CSS pixels (default 168)
     * @return {boolean}           false when the library is missing or the
     *                             value is too long to encode
     */
    function draw(canvas, value, size) {
        if (!canvas || !value || typeof window.qrcode !== 'function') return false;

        size = size || 168;

        var code;
        try {
            // Type 0 lets the library pick the smallest version that fits.
            code = window.qrcode(0, 'M');
            code.addData(value);
            code.make();
        } catch (e) {
            return false;
        }

        var modules = code.getModuleCount();
        // Round the module size to whole pixels and derive the canvas from it,
        // so no module ever straddles a pixel boundary and blurs.
        var scale = Math.max(1, Math.floor(size / (modules + 2)));
        var quiet = scale;                      // one module of quiet zone
        var side = modules * scale + quiet * 2;

        var ratio = window.devicePixelRatio || 1;
        canvas.width = side * ratio;
        canvas.height = side * ratio;
        canvas.style.width = side + 'px';
        canvas.style.height = side + 'px';

        var ctx = canvas.getContext('2d');
        if (!ctx) return false;

        ctx.scale(ratio, ratio);
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, side, side);
        ctx.fillStyle = '#272827';

        for (var row = 0; row < modules; row++) {
            for (var col = 0; col < modules; col++) {
                if (code.isDark(row, col)) {
                    ctx.fillRect(quiet + col * scale, quiet + row * scale, scale, scale);
                }
            }
        }

        return true;
    }

    /** Clipboard copy that still works on plain http, where the API is absent. */
    function copy(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }

        var tmp = document.createElement('textarea');
        tmp.value = text;
        tmp.style.position = 'fixed';
        tmp.style.opacity = '0';
        document.body.appendChild(tmp);
        tmp.select();
        document.execCommand('copy');
        document.body.removeChild(tmp);

        return Promise.resolve();
    }

    return { draw: draw, copy: copy };
})();
