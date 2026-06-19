/**
 * KuBo — Online Presence & Chat (Desktop sidebar + Mobile offcanvas)
 */
(function() {
    'use strict';
    var chatUser = null, chatPoll = null;
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var isMobile = false;

    // ── Determine mobile vs desktop ──
    var offcanvas = document.getElementById('kuboMsgOffcanvas');
    // Desktop sidebar (.kubo-sidebar) is only present on feed page; on non-feed pages
    // it's null, so we default to mobile. On feed page we check if sidebar is visible.
    var sidebar = document.querySelector('.kubo-sidebar');
    isMobile = !sidebar || window.getComputedStyle(sidebar).display === 'none';
    if (offcanvas) {
        new bootstrap.Offcanvas(offcanvas);
    }

    function escHtml(s) { return String(s || '').replace(/&/g, '&').replace(/</g, '<').replace(/>/g, '>').replace(/"/g, '"'); }
    function escAttr(s) { return escHtml(s); }

    // ── Get element by ID, respecting mobile/desktop mode ──
    function $id(name) {
        if (isMobile) {
            var mobEl = document.getElementById(name + 'Mob');
            if (mobEl) return mobEl;
        }
        return document.getElementById(name);
    }

    // ── Presence heartbeat ──
    function ping() {
        fetch('/api/kubo/presence/ping', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } }).catch(function() {});
    }
    ping();
    setInterval(ping, 60000);

    // ── Render online user HTML ──
    function renderOnlineUser(u) {
        var avatar = u.avatar || '';
        var dept = u.department ? '<br><small style="font-size:0.65rem;color:#999;">' + escHtml(u.department) + '</small>' : '';
        var unreadBadge = u.unread > 0 ? '<span class="badge rounded-pill bg-danger ms-auto" style="font-size:0.6rem;">' + u.unread + '</span>' : '';
        return '<div class="kubo-online-user" data-empid="' + escAttr(u.empID) + '" data-name="' + escAttr(u.name) + '" data-avatar="' + escAttr(avatar) + '">'
            + '<span class="dot"></span>'
            + '<img src="' + escHtml(avatar) + '" class="rounded-circle" width="32" height="32" style="object-fit:cover;" alt="">'
            + '<div style="min-width:0;"><span class="small fw-bold d-block text-truncate" style="color:#333;">' + escHtml(u.name) + '</span>' + dept + '</div>'
            + (unreadBadge ? unreadBadge : '<small class="text-muted ms-auto" style="font-size:0.6rem;">' + escHtml(u.last_seen_at) + '</small>')
            + '</div>';
    }

    // ── Load online users (desktop + mobile) ──
    function loadOnline() {
        fetch('/api/kubo/presence/online', { headers: { 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                function fill(listEl, cntEl) {
                    if (!listEl) return;
                    var cnt = d.online ? d.online.length : 0;
                    if (cntEl) cntEl.textContent = cnt + ' active';
                    if (!cnt) {
                        listEl.innerHTML = '<p class="text-muted text-center small py-2 mb-0">No one online right now.</p>';
                        return;
                    }
                    listEl.innerHTML = d.online.map(renderOnlineUser).join('');
                }
                fill($id('kuboOnlineList'), $id('kuboOnlineCount'));
                fill(document.getElementById('kuboOnlineListMob'), document.getElementById('kuboOnlineCountMob'));
            });
    }

    // ── Render conversation user button ──
    function renderConvUser(c) {
        return '<div class="kubo-online-user kubo-conv-user" data-empid="' + escAttr(c.empID) + '" data-name="' + escAttr(c.name) + '" data-avatar="' + escAttr(c.avatar) + '">'
            + '<img src="' + escHtml(c.avatar) + '" class="rounded-circle" width="32" height="32" style="object-fit:cover;" alt="">'
            + '<div style="min-width:0;"><span class="small fw-bold d-block text-truncate" style="color:#333;">' + escHtml(c.name) + '</span></div>'
            + (c.unread > 0 ? '<span class="badge rounded-pill bg-danger ms-auto" style="font-size:0.6rem;">' + c.unread + '</span>' : '')
            + '</div>';
    }

    // ── Load conversation list ──
    function loadConversations() {
        fetch('/api/kubo/conversations', { headers: { 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                function fill(listEl) {
                    if (!listEl) return;
                    if (!d.conversations || !d.conversations.length) {
                        listEl.innerHTML = '<p class="text-muted text-center small py-2 mb-0">No conversations yet.</p>';
                        return;
                    }
                    listEl.innerHTML = d.conversations.map(renderConvUser).join('');
                }
                fill(document.getElementById('kuboConvList'));
                fill(document.getElementById('kuboConvListMob'));

                // Update message badge
                var totalUnread = d.conversations.reduce(function(s, c) { return s + (c.unread || 0); }, 0);
                var badge = document.getElementById('kuboMsgBadge');
                if (badge) {
                    if (totalUnread > 0) { badge.textContent = totalUnread; badge.classList.remove('d-none'); }
                    else { badge.classList.add('d-none'); }
                }
            });
    }
    loadOnline();
    setInterval(loadOnline, 30000);
    loadConversations();
    setInterval(loadConversations, 30000);

    // ── Open chat (desktop + mobile) ──
    function openChat(empID, name, avatar) {
        chatUser = { empID: empID, name: name, avatar: avatar };
        if (isMobile) {
            document.getElementById('kuboChatAvatarMob').src = avatar;
            document.getElementById('kuboChatNameMob').textContent = name;
            var panelMob = document.getElementById('kuboChatPanelMob');
            panelMob.classList.remove('d-none');
            // Auto-open the offcanvas if it's not already visible
            if (offcanvas) {
                var bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvas);
                if (bsOffcanvas && !bsOffcanvas._isShown && !offcanvas.classList.contains('show')) {
                    bsOffcanvas.show();
                }
            }
        } else {
            document.getElementById('kuboChatAvatar').src = avatar;
            document.getElementById('kuboChatName').textContent = name;
            var panel = document.getElementById('kuboChatPanel');
            panel.classList.remove('d-none');
        }
        var msgBox = $id('kuboChatMessages');
        msgBox.innerHTML = '<p class="text-muted text-center small py-2">Loading...</p>';
        loadMessages();
        if (chatPoll) clearInterval(chatPoll);
        chatPoll = setInterval(loadMessages, 5000);
    }

    // ── Click handler: online users + conversations (desktop + mobile) ──
    document.addEventListener('click', function(e) {
        var el = e.target.closest('.kubo-online-user');
        if (el) {
            var name = el.dataset.name, avatar = el.dataset.avatar, empID = el.dataset.empid;
            openChat(empID, name, avatar);
        }
    });

    // ── Close chat ──
    if (document.getElementById('kuboChatClose')) {
        document.getElementById('kuboChatClose').addEventListener('click', function() {
            document.getElementById('kuboChatPanel').classList.add('d-none');
            chatUser = null;
            if (chatPoll) clearInterval(chatPoll);
        });
    }
    if (document.getElementById('kuboChatCloseMob')) {
        document.getElementById('kuboChatCloseMob').addEventListener('click', function() {
            document.getElementById('kuboChatPanelMob').classList.add('d-none');
            chatUser = null;
            if (chatPoll) clearInterval(chatPoll);
        });
    }

    // ── Load messages ──
    function loadMessages() {
        if (!chatUser) return;
        fetch('/api/kubo/messages/' + encodeURIComponent(chatUser.empID), { headers: { 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                var box = $id('kuboChatMessages');
                if (!box) return;
                box.innerHTML = d.messages.map(function(m) {
                    var isMine = m.sender_id !== chatUser.empID;
                    return '<div class="chat-bubble ' + (isMine ? 'mine' : 'theirs') + '">'
                        + escHtml(m.message)
                        + '<div style="font-size:0.6rem;opacity:0.6;margin-top:2px;">' + escHtml(m.created_at) + (isMine ? (m.is_read ? ' ✓✓' : ' ✓') : '') + '</div>'
                        + '</div>';
                }).join('');
                box.scrollTop = box.scrollHeight;
            });
    }

    // ── Send message (desktop + mobile) ──
    function sendMsg() {
        var input = $id('kuboChatInput');
        if (!input) return;
        var msg = input.value.trim();
        if (!msg || !chatUser) return;
        fetch('/api/kubo/messages/' + encodeURIComponent(chatUser.empID), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ message: msg })
        }).then(function(r) { return r.json(); }).then(function() {
            input.value = '';
            loadMessages();
        }).catch(function() {});
    }

    if (document.getElementById('kuboChatSend')) {
        document.getElementById('kuboChatSend').addEventListener('click', sendMsg);
    }
    if (document.getElementById('kuboChatSendMob')) {
        document.getElementById('kuboChatSendMob').addEventListener('click', sendMsg);
    }
    if (document.getElementById('kuboChatInput')) {
        document.getElementById('kuboChatInput').addEventListener('keypress', function(e) { if (e.which === 13) sendMsg(); });
    }
    if (document.getElementById('kuboChatInputMob')) {
        document.getElementById('kuboChatInputMob').addEventListener('keypress', function(e) { if (e.which === 13) sendMsg(); });
    }

    // ── Mobile: messages icon opens offcanvas ──
    var msgToggle = document.getElementById('kuboMessagesToggle');
    if (msgToggle && offcanvas) {
        msgToggle.addEventListener('click', function() {
            var bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvas);
            bsOffcanvas.show();
        });
    }
})();