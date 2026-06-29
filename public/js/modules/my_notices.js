/* My Notices — employee view (read-only) with type + search filter. */
$(document).ready(function () {

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
    function fmtDate(d) { if (!d) return '—'; var dt = new Date(d); return isNaN(dt) ? esc(d) : dt.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }); }

    var allNotices = [];
    var typeFilter = 'all';
    var searchTerm = '';

    function cardHtml(n) {
        var isDisc = n.type === 'disciplinary';
        var typeBadge = isDisc ? '<span class="badge-soft b-disc">Disciplinary Notice</span>' : '<span class="badge-soft b-memo">Memo</span>';
        var cat = n.category ? ' <span class="badge-soft b-cat">' + esc(n.category) + '</span>' : '';
        return '<div class="notice-card ' + (isDisc ? 'disc' : 'memo') + '">' +
            '<div class="nc-head">' +
                '<div><h6 class="nc-title">' + esc(n.title) + '</h6>' +
                '<div class="nc-meta">Issued ' + fmtDate(n.issued_at) + (n.issued_by ? ' &middot; by ' + esc(n.issued_by) : '') + '</div></div>' +
                '<div class="text-nowrap">' + typeBadge + cat + '</div>' +
            '</div>' +
            '<div class="nc-body">' + esc(n.body) + '</div>' +
        '</div>';
    }

    function render() {
        var rows = allNotices.filter(function (n) {
            if (typeFilter !== 'all' && n.type !== typeFilter) return false;
            if (searchTerm) {
                var hay = ((n.title || '') + ' ' + (n.body || '') + ' ' + (n.category || '')).toLowerCase();
                if (hay.indexOf(searchTerm) === -1) return false;
            }
            return true;
        });

        if (!allNotices.length) {
            $('#myNoticeList').html('<div class="empty-state"><i class="fa-solid fa-inbox"></i><div>You have no notices. All clear!</div></div>');
            return;
        }
        if (!rows.length) {
            $('#myNoticeList').html('<div class="empty-state"><i class="fa-solid fa-filter-circle-xmark"></i><div>No notices match this filter.</div></div>');
            return;
        }
        $('#myNoticeList').html(rows.map(cardHtml).join(''));
    }

    function updateCounts() {
        $('#cAll').text(allNotices.length);
        $('#cMemo').text(allNotices.filter(function (n) { return n.type === 'memo'; }).length);
        $('#cDisc').text(allNotices.filter(function (n) { return n.type === 'disciplinary'; }).length);
    }

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

    axios.get('/mynotices/list').then(function (res) {
        allNotices = res.data.data || [];
        updateCounts();
        render();
    }).catch(function () {
        $('#myNoticeList').html('<div class="empty-state"><i class="fa-solid fa-triangle-exclamation"></i><div>Could not load your notices.</div></div>');
    });
});
