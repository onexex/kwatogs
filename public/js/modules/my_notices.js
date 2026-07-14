/* My Notices — employee workspace: list rail + reading pane, signed-memo preview, NTE responses. */
$(document).ready(function () {

    var csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf && window.axios) axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.getAttribute('content');

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
    function fmtDate(d) { if (!d) return '—'; var dt = new Date(d); return isNaN(dt) ? esc(d) : dt.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }); }
    function toast(t, i) { if (window.Swal) Swal.fire({ toast: true, position: 'top-end', timer: 2400, showConfirmButton: false, icon: i || 'success', title: t }); }
    function isOverdue(d) { return d && new Date(d) < new Date(new Date().toDateString()); }

    var allNotices = [];
    var typeFilter = 'all';
    var searchTerm = '';
    var selectedId = null;

    // Compact row in the left rail.
    function rowHtml(n) {
        var isDisc = n.type === 'disciplinary';
        var icon = isDisc ? 'fa-gavel' : 'fa-file-lines';
        var clip = n.has_memo ? ' <span class="clip"><i class="fa-solid fa-paperclip"></i></span>' : '';
        var cat = n.category ? ' <span>&middot; ' + esc(n.category) + '</span>' : '';
        return '<div class="nrow ' + (isDisc ? 'disc' : 'memo') + (n.id === selectedId ? ' active' : '') + '" data-id="' + n.id + '">' +
            '<div class="dot"><i class="fa-solid ' + icon + '"></i></div>' +
            '<div class="rmain">' +
                '<div class="rtitle">' + esc(n.title) + '</div>' +
                '<div class="rmeta">' + fmtDate(n.issued_at) + cat + clip + '</div>' +
            '</div>' +
        '</div>';
    }

    // Full notice in the reading pane.
    function detailHtml(n) {
        var isDisc = n.type === 'disciplinary';
        var typeBadge = isDisc ? '<span class="badge-soft b-disc">Disciplinary Notice</span>' : '<span class="badge-soft b-memo">Memo</span>';
        var cat = n.category ? '<span class="badge-soft b-cat">' + esc(n.category) + '</span>' : '';
        var attach = n.has_memo
            ? '<div class="nd-attach">' +
                '<div class="ai"><i class="fa-solid fa-file-' + (n.memo_isPdf ? 'pdf' : 'image') + '"></i></div>' +
                '<div><div class="an">' + esc(n.attachment_name || 'Signed Memo') + '</div>' +
                '<div class="as">Signed document &middot; view-only, no download</div></div>' +
                '<button class="btn-view-memo" data-id="' + n.id + '" data-pdf="' + (n.memo_isPdf ? 1 : 0) + '" data-title="' + esc(n.attachment_name || 'Signed Memo') + '">' +
                    '<i class="fa-solid fa-eye"></i> View</button>' +
              '</div>'
            : '';
        var readInfo = n.read_at ? '<span><i class="fa-solid fa-check-double"></i>Read ' + fmtDate(n.read_at) + '</span>' : '';
        return '<div class="nd-head">' +
                '<div class="nd-badges">' + typeBadge + cat + '</div>' +
                '<h2 class="nd-title">' + esc(n.title) + '</h2>' +
                '<div class="nd-meta">' +
                    '<span><i class="fa-solid fa-calendar-day"></i>Issued ' + fmtDate(n.issued_at) + '</span>' +
                    (n.issued_by ? '<span><i class="fa-solid fa-user-tie"></i>' + esc(n.issued_by) + '</span>' : '') +
                    readInfo +
                '</div>' +
            '</div>' +
            attach +
            '<div class="nd-body">' + esc(n.body) + '</div>' +
            ackHtml(n) +
            nteHtml(n);
    }

    // Explicit acknowledgement of receipt — disciplinary notices only. Distinct
    // from the passive "Read" receipt: a deliberate act, worded as acknowledging
    // RECEIPT (not agreement), recorded with a timestamp for due process.
    function ackHtml(n) {
        if (n.type !== 'disciplinary' || n.requires_ack != 1) return '';
        if (n.acknowledged_at) {
            return '<div class="ack-box done">' +
                '<i class="fa-solid fa-circle-check"></i>' +
                '<div><div class="ack-t">Receipt acknowledged</div>' +
                '<div class="ack-s">You acknowledged receiving this notice on ' + fmtDate(n.acknowledged_at) + '.</div></div>' +
            '</div>';
        }
        return '<div class="ack-box">' +
            '<div class="ack-h"><i class="fa-solid fa-signature"></i> Acknowledge Receipt</div>' +
            '<div class="ack-s">Please confirm that you have received this notice. This acknowledges <strong>receipt only</strong> — it is not an admission of fault or agreement with its contents.</div>' +
            '<button class="ack-btn" id="btnAcknowledge" data-id="' + n.id + '"><i class="fa-solid fa-check"></i> I acknowledge receipt of this notice</button>' +
        '</div>';
    }

    // Notice to Explain block: response form (before submit) or the submitted
    // explanation + HR decision (after).
    function nteHtml(n) {
        if (!(n.type === 'disciplinary' && n.requires_response == 1)) return '';

        if (n.response_at) {
            var docLink = n.has_response_doc
                ? '<a href="/notices/' + n.id + '/response-doc" target="_blank" class="nte-doc"><i class="fa-solid fa-paperclip"></i> ' + esc(n.response_doc_name) + '</a>'
                : '';
            var decision;
            if (n.response_decision === 'accepted') decision = '<div class="nte-decision ok"><i class="fa-solid fa-circle-check"></i> HR accepted your explanation' + (n.response_review_note ? ': ' + esc(n.response_review_note) : '') + '</div>';
            else if (n.response_decision === 'further_action') decision = '<div class="nte-decision warn"><i class="fa-solid fa-gavel"></i> For further action' + (n.response_review_note ? ': ' + esc(n.response_review_note) : '') + '</div>';
            else decision = '<div class="nte-decision pend"><i class="fa-solid fa-hourglass-half"></i> Awaiting HR review</div>';
            return '<div class="nte-box submitted">' +
                '<div class="nte-h"><i class="fa-solid fa-file-pen"></i> Your Explanation</div>' +
                '<div class="nte-sub">Submitted ' + fmtDate(n.response_at) + '</div>' +
                '<div class="nte-body">' + esc(n.response_body) + '</div>' + docLink + decision +
            '</div>';
        }

        var overdue = isOverdue(n.respond_by);
        var sub = n.respond_by
            ? (overdue ? '<span class="od">Overdue — the deadline was ' + fmtDate(n.respond_by) + '. Please still respond.</span>' : 'Please submit your written explanation on or before ' + fmtDate(n.respond_by) + '.')
            : 'You are required to submit a written explanation for this notice.';
        return '<div class="nte-box ' + (overdue ? 'od' : '') + '">' +
            '<div class="nte-h"><i class="fa-solid fa-triangle-exclamation"></i> Notice to Explain</div>' +
            '<div class="nte-sub">' + sub + '</div>' +
            '<textarea class="nte-input" id="nteBody" rows="5" placeholder="Write your explanation here…"></textarea>' +
            '<div class="nte-file"><label><i class="fa-solid fa-paperclip"></i> Attach supporting file (optional — PDF or image)</label>' +
                '<input type="file" id="nteFile" accept=".pdf,.jpg,.jpeg,.png"></div>' +
            '<div class="nte-err" id="nteErr"></div>' +
            '<button class="nte-submit" id="btnSubmitResponse" data-id="' + n.id + '"><i class="fa-solid fa-paper-plane"></i> Submit Explanation</button>' +
        '</div>';
    }

    function selectNotice(id) {
        var n = null;
        for (var i = 0; i < allNotices.length; i++) { if (allNotices[i].id === id) { n = allNotices[i]; break; } }
        if (!n) return;
        selectedId = id;
        $('.nrow').removeClass('active');
        $('.nrow[data-id="' + id + '"]').addClass('active');
        $('#mnDetail').html(detailHtml(n));
    }

    /* ── Signed-memo preview (view-only, no download, watermarked) ── */
    var watermarkText = ($('.mn-shell').data('watermark') || 'Confidential') + '';

    function watermarkBg() {
        var stamp = watermarkText + '  ·  ' + new Date().toLocaleString();
        var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="440" height="240">' +
            '<text x="20" y="130" transform="rotate(-30 220 120)" fill="rgba(15,23,42,0.16)" ' +
            'font-family="Arial, sans-serif" font-size="15" font-weight="bold">' +
            String(stamp).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') +
            '</text></svg>';
        return 'url("data:image/svg+xml;utf8,' + encodeURIComponent(svg) + '")';
    }

    var $viewer = $('#memoViewer'), $stage = $('#mvStage'), $doc = $('#mvDoc');
    var renderToken = 0;   // invalidates an in-flight render if the modal is closed/reopened

    function stageWidth() { return Math.max(200, ($stage.width() || 640) - 40); }

    // PDF → one <canvas> per page. No iframe, so the native viewer's download/print
    // controls and its right-click menu never exist.
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
            if (token === renderToken) $doc.html('<div class="mv-loading"><i class="fa-solid fa-triangle-exclamation"></i> Could not load this document.</div>');
        });
    }

    // Image → drawn onto a canvas (so there is no <img> element to "Save image as").
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
        img.onerror = function () { if (token === renderToken) $doc.html('<div class="mv-loading"><i class="fa-solid fa-triangle-exclamation"></i> Could not load this image.</div>'); };
        img.src = url;
    }

    function openMemo(id, isPdf, title) {
        renderToken++;
        var token = renderToken;
        $('#mvTitle').text(title || 'Signed Memo');
        $('#mvWatermark').css('background-image', watermarkBg());
        $stage.removeClass('blurred');
        $doc.html('<div class="mv-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading document…</div>');
        $viewer.addClass('open');
        var url = '/notices/' + id + '/memo';
        if (isPdf) {
            if (!window.pdfjsLib) { $doc.html('<div class="mv-loading"><i class="fa-solid fa-triangle-exclamation"></i> PDF viewer unavailable.</div>'); return; }
            renderPdf(url, token);
        } else {
            renderImage(url, token);
        }
    }

    function closeMemo() {
        renderToken++;   // cancel any pending page renders
        $viewer.removeClass('open');
        $doc.empty();
    }

    $(document).on('click', '.btn-view-memo', function () {
        openMemo($(this).data('id'), $(this).data('pdf') == 1, $(this).data('title'));
    });
    $(document).on('click', '#mvClose', closeMemo);
    $(document).on('click', '#memoViewer', function (e) { if (e.target === this) closeMemo(); });

    // Deterrents — friction + traceability, NOT a hard block (screenshots can't be
    // stopped in a browser; the watermark makes any capture traceable to the viewer).
    $(document).on('keydown', function (e) {
        if (!$viewer.hasClass('open')) return;
        var k = (e.key || '').toLowerCase();
        if (e.key === 'Escape') { closeMemo(); return; }
        if ((e.ctrlKey || e.metaKey) && (k === 'p' || k === 's')) { e.preventDefault(); }   // print / save
        if (e.key === 'PrintScreen') { $stage.addClass('blurred'); }
    });
    $(document).on('contextmenu', '#mvStage', function () { return false; });
    $(document).on('dragstart', '#mvStage canvas', function () { return false; });
    // Blur the document whenever the tab/window loses focus (deters app-switch capture tools).
    $(window).on('blur', function () { if ($viewer.hasClass('open')) $stage.addClass('blurred'); });
    $(window).on('focus', function () { $stage.removeClass('blurred'); });
    document.addEventListener('visibilitychange', function () {
        if (document.hidden && $viewer.hasClass('open')) $stage.addClass('blurred');
        else $stage.removeClass('blurred');
    });

    function visibleRows() {
        return allNotices.filter(function (n) {
            if (typeFilter !== 'all' && n.type !== typeFilter) return false;
            if (searchTerm) {
                var hay = ((n.title || '') + ' ' + (n.body || '') + ' ' + (n.category || '')).toLowerCase();
                if (hay.indexOf(searchTerm) === -1) return false;
            }
            return true;
        });
    }

    function render() {
        if (!allNotices.length) {
            $('#myNoticeList').html('<div class="empty-state"><i class="fa-solid fa-inbox"></i><div>You have no notices. All clear!</div></div>');
            $('#mnDetail').html('<div class="nd-empty"><i class="fa-solid fa-mug-hot"></i><div>Nothing here yet. HR notices will appear on this page.</div></div>');
            return;
        }
        var rows = visibleRows();
        if (!rows.length) {
            $('#myNoticeList').html('<div class="empty-state"><i class="fa-solid fa-filter-circle-xmark"></i><div>No notices match this filter.</div></div>');
            return;
        }
        $('#myNoticeList').html(rows.map(rowHtml).join(''));

        // Keep the current selection if it's still visible; otherwise open the first row.
        var stillVisible = false;
        for (var i = 0; i < rows.length; i++) { if (rows[i].id === selectedId) { stillVisible = true; break; } }
        if (!stillVisible) { selectNotice(rows[0].id); }
        else { $('.nrow[data-id="' + selectedId + '"]').addClass('active'); }
    }

    function updateCounts() {
        var memo = allNotices.filter(function (n) { return n.type === 'memo'; }).length;
        var disc = allNotices.filter(function (n) { return n.type === 'disciplinary'; }).length;
        var docs = allNotices.filter(function (n) { return n.has_memo; }).length;
        $('#cAll').text(allNotices.length); $('#cMemo').text(memo); $('#cDisc').text(disc);
        $('#sTotal').text(allNotices.length); $('#sMemo').text(memo); $('#sDisc').text(disc); $('#sDocs').text(docs);
    }

    $(document).on('click', '.nrow', function () { selectNotice($(this).data('id')); });

    $(document).on('click', '.pill', function () {
        $('.pill').removeClass('active');
        $(this).addClass('active');
        typeFilter = $(this).data('filter');
        render();
    });

    var t;
    $(document).on('input', '#mnSearch', function () {
        var v = $(this).val().toLowerCase();
        clearTimeout(t);
        t = setTimeout(function () { searchTerm = v; render(); }, 200);
    });

    function loadNotices() {
        return axios.get('/mynotices/list').then(function (res) {
            allNotices = res.data.data || [];
            updateCounts();
            render();
        }).catch(function () {
            $('#myNoticeList').html('<div class="empty-state"><i class="fa-solid fa-triangle-exclamation"></i><div>Could not load your notices.</div></div>');
        });
    }

    // Acknowledge receipt of a disciplinary notice (once). Confirm first, then
    // reload so the pane shows the acknowledged state.
    $(document).on('click', '#btnAcknowledge', function () {
        var $btn = $(this), id = $btn.data('id');
        function post() {
            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Acknowledging…');
            axios.post('/mynotices/' + id + '/acknowledge').then(function (res) {
                if (res.data.status === 200) {
                    toast(res.data.msg || 'Receipt acknowledged.', 'success');
                    var n = null;
                    for (var i = 0; i < allNotices.length; i++) { if (allNotices[i].id === id) { n = allNotices[i]; break; } }
                    if (n) { n.acknowledged_at = res.data.acknowledged_at; selectNotice(id); }
                    else { loadNotices().then(function () { selectNotice(id); }); }
                } else {
                    toast(res.data.msg || 'Could not acknowledge.', 'error');
                    loadNotices().then(function () { selectNotice(id); });
                }
            }).catch(function () {
                toast('Error acknowledging this notice.', 'error');
                $btn.prop('disabled', false).html('<i class="fa-solid fa-check"></i> I acknowledge receipt of this notice');
            });
        }
        if (window.Swal) {
            Swal.fire({
                icon: 'question',
                title: 'Acknowledge receipt?',
                text: 'This records that you received this notice on today\'s date. It does not mean you agree with it.',
                showCancelButton: true,
                confirmButtonText: 'Yes, acknowledge',
                confirmButtonColor: '#008080'
            }).then(function (r) { if (r.isConfirmed) post(); });
        } else {
            post();
        }
    });

    // Submit an NTE explanation (once). Reloads so the pane shows the read-only,
    // "awaiting HR review" state.
    $(document).on('click', '#btnSubmitResponse', function () {
        var $btn = $(this), id = $btn.data('id');
        var body = ($('#nteBody').val() || '').trim();
        $('#nteErr').text('');
        if (!body) { $('#nteErr').text('Please write your explanation.'); return; }

        var fd = new FormData();
        fd.append('response_body', body);
        var f = document.getElementById('nteFile').files[0];
        if (f) fd.append('attachment', f);

        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Submitting…');
        axios.post('/mynotices/' + id + '/respond', fd).then(function (res) {
            if (res.data.status === 201) {
                $('#nteErr').text((res.data.error.response_body && res.data.error.response_body[0]) || (res.data.error.attachment && res.data.error.attachment[0]) || 'Please check your input.');
                $btn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane"></i> Submit Explanation');
                return;
            }
            if (res.data.status === 200) {
                toast(res.data.msg, 'success');
                // render() keeps the current selection but doesn't repaint #mnDetail
                // when the row stays visible, so refresh the pane explicitly — otherwise
                // it lingers on the old form with the disabled "Submitting…" button.
                loadNotices().then(function () { selectNotice(id); });
            } else {
                $('#nteErr').text(res.data.msg || 'Could not submit.');
                $btn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane"></i> Submit Explanation');
            }
        }).catch(function () {
            $('#nteErr').text('Error submitting your explanation.');
            $btn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane"></i> Submit Explanation');
        });
    });

    loadNotices();
});
