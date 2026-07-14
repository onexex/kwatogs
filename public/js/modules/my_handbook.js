/* My Handbook — employee workspace: section rail + reading pane, acknowledgement,
   view-only supporting-document preview (shared pattern with My Notices). */
$(document).ready(function () {

    var csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf && window.axios) axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.getAttribute('content');

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
    function fmtDate(d) { if (!d) return '—'; var dt = new Date(d); return isNaN(dt) ? esc(d) : dt.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }); }
    function toast(t, i) { if (window.Swal) Swal.fire({ toast: true, position: 'top-end', timer: 2400, showConfirmButton: false, icon: i || 'success', title: t }); }

    var sections = [];
    var searchTerm = '';
    var selectedId = null;

    // Compact row in the left rail. `idx` is the 1-based position (null for the
    // pinned master document, which shows an icon instead of a number).
    function rowHtml(s, idx) {
        var clip = s.has_doc ? ' <span class="clip"><i class="fa-solid fa-paperclip"></i></span>' : '';
        var pill = '';
        if (s.requires_ack) {
            if (s.needs_reack) pill = ' <span class="pill-mini pm-re">Re-acknowledge</span>';
            else if (s.acknowledged) pill = ' <span class="pill-mini pm-ack">Acknowledged</span>';
            else pill = ' <span class="pill-mini pm-need">Acknowledge</span>';
        }
        var num = s.is_master
            ? '<div class="num" style="background:var(--teal);color:#fff;"><i class="fa-solid fa-book"></i></div>'
            : '<div class="num">' + idx + '</div>';
        var meta = s.is_master ? 'Complete handbook' : fmtDate(s.updated_at);
        return '<div class="srow' + (s.is_master ? ' master' : '') + (s.id === selectedId ? ' active' : '') + '" data-id="' + s.id + '">' +
            num +
            '<div class="rmain">' +
                '<div class="rtitle">' + esc(s.title) + '</div>' +
                '<div class="rmeta">' + meta + clip + pill + '</div>' +
            '</div>' +
        '</div>';
    }

    // Full section in the reading pane. Body is HR-authored HTML (trusted).
    function detailHtml(s) {
        var doc = s.has_doc
            ? '<div class="hd-doc">' +
                '<div class="ai"><i class="fa-solid fa-file-' + (s.doc_isPdf ? 'pdf' : 'image') + '"></i></div>' +
                '<div><div class="an">' + esc(s.attachment_name || 'Supporting document') + '</div>' +
                '<div class="as">Attached document &middot; view-only, no download</div></div>' +
                '<button class="btn-view-doc" data-id="' + s.id + '" data-pdf="' + (s.doc_isPdf ? 1 : 0) + '" data-title="' + esc(s.attachment_name || 'Document') + '">' +
                    '<i class="fa-solid fa-eye"></i> View</button>' +
              '</div>'
            : '';

        // The master document has no authored body — the PDF itself is the content.
        var bodyHtml = s.is_master
            ? (s.body || '<p style="color:var(--slate-light)">This is the complete Employee Handbook. Click <b>View</b> above to open and read it.</p>')
            : (s.body || '<p style="color:var(--muted)">No content yet.</p>');

        return '<div class="hd-head">' +
                '<div class="hd-kicker">' + (s.is_master ? 'Complete Handbook' : 'Handbook') + '</div>' +
                '<h1 class="hd-title">' + esc(s.title) + '</h1>' +
                '<div class="hd-meta">' +
                    '<span><i class="fa-solid fa-clock-rotate-left"></i>Updated ' + fmtDate(s.updated_at) + '</span>' +
                    (s.updated_by ? '<span><i class="fa-solid fa-user-tie"></i>' + esc(s.updated_by) + '</span>' : '') +
                '</div>' +
            '</div>' +
            doc +
            '<div class="hd-body">' + bodyHtml + '</div>' +
            ackHtml(s);
    }

    function ackHtml(s) {
        if (!s.requires_ack) return '';
        if (s.acknowledged) {
            return '<div class="hb-ack done">' +
                '<div class="ah"><i class="fa-solid fa-circle-check"></i> You have acknowledged this section</div>' +
                '<div class="asub">Thank you. Your acknowledgement is on record.</div>' +
            '</div>';
        }
        var re = s.needs_reack
            ? 'This section was updated since you last acknowledged it. Please review and acknowledge the current version.'
            : 'Please confirm that you have read and understood this section.';
        return '<div class="hb-ack">' +
            '<div class="ah"><i class="fa-solid fa-triangle-exclamation"></i> Acknowledgement required</div>' +
            '<div class="asub">' + re + '</div>' +
            '<button class="btn-ack" id="btnAck" data-id="' + s.id + '"><i class="fa-solid fa-check"></i> I have read and understood</button>' +
        '</div>';
    }

    function selectSection(id) {
        var s = null;
        for (var i = 0; i < sections.length; i++) { if (sections[i].id === id) { s = sections[i]; break; } }
        if (!s) return;
        selectedId = id;
        $('.srow').removeClass('active');
        $('.srow[data-id="' + id + '"]').addClass('active');
        $('#hbDetail').html(detailHtml(s));
        $('#hbDetail').scrollTop(0);
    }

    /* ── Acknowledge ── */
    $(document).on('click', '#btnAck', function () {
        var $btn = $(this), id = $btn.data('id');
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Saving…');
        axios.post('/myhandbook/' + id + '/acknowledge').then(function (res) {
            if (res.data.status === 200) {
                for (var i = 0; i < sections.length; i++) {
                    if (sections[i].id === id) { sections[i].acknowledged = 1; sections[i].needs_reack = 0; break; }
                }
                toast(res.data.msg, 'success');
                updateCounts();
                render();
                selectSection(id);
            } else {
                toast(res.data.msg || 'Could not save.', 'warning');
                $btn.prop('disabled', false).html('<i class="fa-solid fa-check"></i> I have read and understood');
            }
        }).catch(function () {
            toast('Error saving your acknowledgement.', 'error');
            $btn.prop('disabled', false).html('<i class="fa-solid fa-check"></i> I have read and understood');
        });
    });

    /* ── Supporting-document preview (view-only, no download, watermarked) ── */
    var watermarkText = ($('.hb-shell').data('watermark') || 'Confidential') + '';

    function watermarkBg() {
        var stamp = watermarkText + '  ·  ' + new Date().toLocaleString();
        var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="440" height="240">' +
            '<text x="20" y="130" transform="rotate(-30 220 120)" fill="rgba(15,23,42,0.16)" ' +
            'font-family="Arial, sans-serif" font-size="15" font-weight="bold">' +
            String(stamp).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') +
            '</text></svg>';
        return 'url("data:image/svg+xml;utf8,' + encodeURIComponent(svg) + '")';
    }

    var $viewer = $('#docViewer'), $stage = $('#dvStage'), $doc = $('#dvDoc');
    var renderToken = 0;

    function stageWidth() { return Math.max(200, ($stage.width() || 640) - 40); }

    function renderPdf(url, token) {
        pdfjsLib.getDocument({ url: url, withCredentials: true }).promise.then(function (pdf) {
            var chain = Promise.resolve();
            for (var p = 1; p <= pdf.numPages; p++) {
                (function (pageNum) {
                    chain = chain.then(function () {
                        if (token !== renderToken) return;
                        return pdf.getPage(pageNum).then(function (page) {
                            if (token !== renderToken) return;
                            var base = page.getViewport({ scale: 1 });
                            var scale = stageWidth() / base.width;
                            var vp = page.getViewport({ scale: scale * (window.devicePixelRatio || 1) });
                            var canvas = document.createElement('canvas');
                            canvas.width = vp.width; canvas.height = vp.height;
                            canvas.style.width = (vp.width / (window.devicePixelRatio || 1)) + 'px';
                            if (pageNum === 1) $doc.empty();
                            $doc.append(canvas);
                            return page.render({ canvasContext: canvas.getContext('2d'), viewport: vp }).promise;
                        });
                    });
                })(p);
            }
            return chain;
        }).catch(function () {
            if (token === renderToken) $doc.html('<div class="dv-loading"><i class="fa-solid fa-triangle-exclamation"></i> Could not load this document.</div>');
        });
    }

    function renderImage(url, token) {
        var img = new Image();
        img.onload = function () {
            if (token !== renderToken) return;
            var scale = Math.min(1, stageWidth() / img.naturalWidth) * (window.devicePixelRatio || 1);
            var canvas = document.createElement('canvas');
            canvas.width = img.naturalWidth * scale; canvas.height = img.naturalHeight * scale;
            canvas.style.width = (canvas.width / (window.devicePixelRatio || 1)) + 'px';
            canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
            $doc.empty().append(canvas);
        };
        img.onerror = function () { if (token === renderToken) $doc.html('<div class="dv-loading"><i class="fa-solid fa-triangle-exclamation"></i> Could not load this image.</div>'); };
        img.src = url;
    }

    function openDoc(id, isPdf, title) {
        renderToken++;
        var token = renderToken;
        $('#dvTitle').text(title || 'Document');
        $('#dvWatermark').css('background-image', watermarkBg());
        $stage.removeClass('blurred');
        $doc.html('<div class="dv-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading document…</div>');
        $viewer.addClass('open');
        var url = '/handbook/' + id + '/doc';
        if (isPdf) {
            if (!window.pdfjsLib) { $doc.html('<div class="dv-loading"><i class="fa-solid fa-triangle-exclamation"></i> PDF viewer unavailable.</div>'); return; }
            renderPdf(url, token);
        } else {
            renderImage(url, token);
        }
    }

    function closeDoc() {
        renderToken++;
        $viewer.removeClass('open');
        $doc.empty();
    }

    $(document).on('click', '.btn-view-doc', function () {
        openDoc($(this).data('id'), $(this).data('pdf') == 1, $(this).data('title'));
    });
    $(document).on('click', '#dvClose', closeDoc);
    $(document).on('click', '#docViewer', function (e) { if (e.target === this) closeDoc(); });

    $(document).on('keydown', function (e) {
        if (!$viewer.hasClass('open')) return;
        var k = (e.key || '').toLowerCase();
        if (e.key === 'Escape') { closeDoc(); return; }
        if ((e.ctrlKey || e.metaKey) && (k === 'p' || k === 's')) { e.preventDefault(); }
        if (e.key === 'PrintScreen') { $stage.addClass('blurred'); }
    });
    $(document).on('contextmenu', '#dvStage', function () { return false; });
    $(document).on('dragstart', '#dvStage canvas', function () { return false; });
    $(window).on('blur', function () { if ($viewer.hasClass('open')) $stage.addClass('blurred'); });
    $(window).on('focus', function () { $stage.removeClass('blurred'); });
    document.addEventListener('visibilitychange', function () {
        if (document.hidden && $viewer.hasClass('open')) $stage.addClass('blurred');
        else $stage.removeClass('blurred');
    });

    /* ── List rendering ── */
    function visibleRows() {
        return sections.filter(function (s) {
            if (!searchTerm) return true;
            var hay = ((s.title || '') + ' ' + (s.body || '')).toLowerCase();
            return hay.indexOf(searchTerm) !== -1;
        });
    }

    // 1-based position of a section among authored (non-master) sections.
    function numberOf(s) {
        var n = 0;
        for (var i = 0; i < sections.length; i++) {
            if (!sections[i].is_master) {
                n++;
                if (sections[i].id === s.id) return n;
            }
        }
        return '';
    }

    function render() {
        if (!sections.length) {
            $('#handbookList').html('<div class="empty-state"><i class="fa-solid fa-book"></i><div>The handbook has no sections yet.</div></div>');
            $('#hbDetail').html('<div class="hd-empty"><i class="fa-solid fa-book"></i><div>Nothing here yet. Handbook sections published by HR will appear on this page.</div></div>');
            return;
        }
        var rows = visibleRows();
        if (!rows.length) {
            $('#handbookList').html('<div class="empty-state"><i class="fa-solid fa-filter-circle-xmark"></i><div>No sections match your search.</div></div>');
            return;
        }
        // Number chips reflect the section's real position among authored sections
        // (the pinned master doc is not numbered), so they stay stable while searching.
        $('#handbookList').html(rows.map(function (s) { return rowHtml(s, numberOf(s)); }).join(''));

        var stillVisible = false;
        for (var i = 0; i < rows.length; i++) { if (rows[i].id === selectedId) { stillVisible = true; break; } }
        if (!stillVisible) { selectSection(rows[0].id); }
        else { $('.srow[data-id="' + selectedId + '"]').addClass('active'); }
    }

    function updateCounts() {
        var reqd = sections.filter(function (s) { return s.requires_ack; });
        var done = reqd.filter(function (s) { return s.acknowledged; }).length;
        var pend = reqd.length - done;
        $('#sTotal').text(sections.length);
        $('#sAck').text(done);
        $('#sPend').text(pend);
        var pct = reqd.length ? Math.round((done / reqd.length) * 100) : 100;
        $('#hbBar').css('width', pct + '%');
        $('#hbPct').text(pct + '%');
    }

    $(document).on('click', '.srow', function () { selectSection($(this).data('id')); });

    var t;
    $(document).on('input', '#hbSearch', function () {
        var v = $(this).val().toLowerCase();
        clearTimeout(t);
        t = setTimeout(function () { searchTerm = v; render(); }, 200);
    });

    function loadSections() {
        return axios.get('/myhandbook/list').then(function (res) {
            sections = res.data.data || [];
            updateCounts();
            render();
        }).catch(function () {
            $('#handbookList').html('<div class="empty-state"><i class="fa-solid fa-triangle-exclamation"></i><div>Could not load the handbook.</div></div>');
        });
    }

    loadSections();
});
