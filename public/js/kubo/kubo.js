/**
 * KuBo - Instagram-Inspired Community Platform
 * jQuery AJAX Module
 * SECURITY: All HTML output sanitized to prevent XSS
 */

(function ($, window) {
    'use strict';

    // HTML entity encoding for safe text output
    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&')
            .replace(/</g, '<')
            .replace(/>/g, '>')
            .replace(/"/g, '"')
            .replace(/'/g, '&#39;');
    }

    // Safe attribute encoding
    function escAttr(str) {
        return escHtml(str);
    }

    // Safe URL encoding (prevents javascript: protocol injection)
    function escUrl(str) {
        if (!str) return '';
        var s = String(str).trim().toLowerCase();
        if (s.startsWith('javascript:') || s.startsWith('data:') || s.startsWith('vbscript:')) {
            return '';
        }
        return escHtml(str);
    }

    var KuBo = {
        currentPage: 1,
        hasMore: true,
        loading: false,
        reactionEmojis: {
            love: '❤️', like: '👍', fire: '🔥', laugh: '😂', clap: '👏', celebrate: '🎉'
        },
        csrf: $('meta[name="csrf-token"]').attr('content'),

        init: function () {
            this.initFeed();
            this.initPostCreator();
            this.initReactions();
            this.initComments();
            this.initReposts();
            this.initNotifications();
            this.initExplore();
            this.initProfile();
            this.initLightbox();
        },

        // ============================================
        // FEED
        // ============================================
        initFeed: function () {
            var self = this;
            if (!$('#kuboFeedContainer').length) return;
            this.loadFeed();
            // Use the inner feed scroll container if present, otherwise the window
            var $scroller = $('#kuboScroll').length ? $('#kuboScroll') : $(window);
            $scroller.on('scroll', function () {
                if (self.loading || !self.hasMore) return;
                var el = $('#kuboScroll').length ? $('#kuboScroll')[0] : null;
                var nearBottom;
                if (el) {
                    nearBottom = (el.scrollHeight - el.scrollTop - el.clientHeight) < 400;
                } else {
                    nearBottom = ($(document).height() - $(window).scrollTop() - $(window).height()) < 400;
                }
                if (nearBottom) self.loadFeed();
            });
        },

        loadFeed: function () {
            var self = this;
            if (this.loading || !this.hasMore) return;
            this.loading = true;
            $('#kuboFeedLoader').removeClass('d-none');
            $.get('/api/kubo/feed', { page: this.currentPage }, function (res) {
                $('#kuboFeedLoader').addClass('d-none');
                self.hasMore = res.has_more;
                if (res.posts && res.posts.length > 0) {
                    $.each(res.posts, function (i, post) { $('#kuboFeedContainer').append(self.renderPostCard(post)); });
                    self.currentPage++;
                }
                if (!self.hasMore) $('#kuboFeedEnd').removeClass('d-none');
                self.loading = false;
            }).fail(function () { $('#kuboFeedLoader').addClass('d-none'); self.loading = false; });
        },

        renderPostCard: function (post) {
            var r = this.reactionEmojis;
            var currentEmoji = post.current_reaction ? r[post.current_reaction] || '❤️' : '';
            var images = post.images || [];
            var isSingle = images.length === 1;
            var html = '<div class="card border-0 shadow-sm mb-3 kubo-post-card" data-post-id="' + parseInt(post.id) + '" style="border-radius:12px;overflow:hidden;">';
            if (post.is_pinned) html += '<div class="px-3 pt-2"><small class="text-muted"><i class="fas fa-thumbtack me-1"></i>Pinned post</small></div>';
            if (post.is_announcement) html += '<div class="px-3 pt-2"><span class="badge" style="background:#008080;"><i class="fas fa-bullhorn me-1"></i>Announcement</span></div>';
            html += '<div class="card-header bg-white border-0 d-flex align-items-center justify-content-between px-3 pt-3 pb-0">';
            html += '<div class="d-flex align-items-center">';
            html += '<a href="/kubo/profile/' + escAttr(post.user.empID) + '"><img src="' + escUrl(post.user.avatar) + '" class="rounded-circle" width="40" height="40" style="object-fit:cover;border:2px solid #e0e0e0;" alt="Avatar"></a>';
            html += '<div class="ms-3">';
            html += '<a href="/kubo/profile/' + escAttr(post.user.empID) + '" class="text-decoration-none text-dark fw-bold small d-block">' + escHtml(post.user.name) + '</a>';
            html += '<small class="text-muted" style="font-size:0.7rem;">' + escHtml(post.user.department || '') + (post.user.position ? ' &middot; ' + escHtml(post.user.position) : '') + '</small>';
            html += '<small class="text-muted d-block" style="font-size:0.65rem;">' + escHtml(post.created_at) + '</small>';
            html += '</div></div>';
            if (post.can_delete || post.can_pin) {
                html += '<div class="dropdown"><button class="btn btn-link text-dark p-0" data-bs-toggle="dropdown" style="font-size:1.2rem;"><i class="fas fa-ellipsis-h"></i></button>';
                html += '<ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm">';
                if (post.can_pin) html += '<li><a class="dropdown-item kubo-pin-post" href="javascript:void(0)" data-post-id="' + parseInt(post.id) + '"><i class="fas fa-thumbtack me-2"></i>' + (post.is_pinned ? 'Unpin' : 'Pin') + '</a></li>';
                if (post.can_delete) html += '<li><a class="dropdown-item kubo-delete-post" href="javascript:void(0)" data-post-id="' + parseInt(post.id) + '"><i class="fas fa-trash-alt text-danger me-2"></i>Delete</a></li>';
                html += '</ul></div>';
            }
            html += '</div>';
            if (post.content) {
                // content_html is server-rendered with hashtag links and properly escaped
                html += '<div class="card-body px-3 pt-2 pb-0"><p class="mb-0" style="font-size:0.9rem;line-height:1.5;word-break:break-word;">' + (post.content_html || escHtml(post.content)) + '</p></div>';
            }
            if (images.length > 0) {
                html += '<div class="mt-2">';
                if (isSingle) html += '<img src="' + escUrl(images[0].url) + '" class="img-fluid w-100 kubo-lightbox-trigger" style="max-height:500px;object-fit:cover;cursor:pointer;" alt="Post image">';
                else {
                    html += '<div class="kubo-carousel position-relative" style="background:#000;">';
                    html += '<div class="kubo-carousel-inner d-flex" style="overflow-x:auto;scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch;">';
                    $.each(images, function (idx, img) { html += '<div class="flex-shrink-0 w-100" style="scroll-snap-align:start;"><img src="' + escUrl(img.url) + '" class="img-fluid w-100 kubo-lightbox-trigger" style="max-height:500px;object-fit:contain;cursor:pointer;min-height:300px;" alt="Post image"></div>'; });
                    html += '</div><div class="text-center pb-2 pt-1 position-absolute bottom-0 w-100">';
                    $.each(images, function (idx) { var opacity = idx === 0 ? '0.9' : '0.4'; html += '<span class="carousel-dot" data-index="' + idx + '" style="display:inline-block;width:6px;height:6px;border-radius:50%;background:rgba(255,255,255,' + opacity + ');margin:0 3px;cursor:pointer;"></span>'; });
                    html += '</div></div>';
                }
                html += '</div>';
            }
            var reactionColor = post.current_reaction ? 'color:#ED4956 !important;' : '';
            var repostColor = post.is_reposted_by_me ? 'color:#008080 !important;' : '';
            html += '<div class="card-body px-3 py-2"><div class="d-flex align-items-center gap-3">';
            html += '<div class="kubo-reaction-wrapper position-relative">';
            html += '<button class="btn btn-link text-dark p-0 border-0 kubo-react-btn" data-post-id="' + parseInt(post.id) + '" style="font-size:1.4rem;text-decoration:none;' + reactionColor + '">';
            html += post.current_reaction ? currentEmoji : '<i class="far fa-heart"></i>';
            html += '</button><span class="kubo-reactions-count fw-semibold small ms-1">' + (post.reactions ? parseInt(post.reactions.total) : 0) + '</span></div>';
            html += '<button class="btn btn-link text-dark p-0 border-0 kubo-comment-toggle" data-post-id="' + parseInt(post.id) + '" style="font-size:1.4rem;"><i class="far fa-comment"></i></button>';
            html += '<span class="kubo-comments-count small fw-semibold">' + parseInt(post.comments_count || 0) + '</span>';
            html += '<button class="btn btn-link text-dark p-0 border-0 kubo-repost-btn" data-post-id="' + parseInt(post.id) + '" style="font-size:1.4rem;' + repostColor + '"><i class="fas fa-retweet"></i></button>';
            html += '<span class="kubo-reposts-count small fw-semibold">' + parseInt(post.reposts_count || 0) + '</span>';
            html += '</div></div>';
            html += '<div class="kubo-comments-section d-none border-top px-3 py-2" style="background:#fafafa;">';
            html += '<div class="kubo-comments-list small mb-2" style="max-height:200px;overflow-y:auto;"></div>';
            html += '<div class="d-flex align-items-center gap-2">';
            html += '<input type="text" class="form-control form-control-sm rounded-pill kubo-comment-input" placeholder="Add a comment..." style="border:1px solid #e0e0e0;font-size:0.8rem;">';
            html += '<button class="btn btn-sm fw-bold kubo-comment-submit" style="color:#008080;font-size:0.8rem;" data-post-id="' + parseInt(post.id) + '">Post</button>';
            html += '</div></div></div>';
            return html;
        },

        // ============================================
        // POST CREATOR
        // ============================================
        initPostCreator: function () {
            var self = this, uploadedImages = [];
            $('#kuboPostContent').on('input', function () { $('#kuboPostSubmit').prop('disabled', !$(this).val().trim() && uploadedImages.length === 0); });
            $('#kuboImageInput').on('change', function () {
                var files = this.files; if (!files.length) return;
                var formData = new FormData();
                $.each(files, function (i, file) { formData.append('images[]', file); });
                formData.append('_token', self.csrf);
                $.ajax({ url: '/api/kubo/upload/images', type: 'POST', data: formData, processData: false, contentType: false,
                    success: function (res) { uploadedImages = res.images; var h = ''; $.each(res.urls, function (i, url) { h += '<div class="position-relative" style="width:80px;height:80px;"><img src="' + escUrl(url) + '" class="rounded" style="width:100%;height:100%;object-fit:cover;" alt="Preview"><button class="btn btn-danger btn-sm position-absolute top-0 end-0 rounded-circle kubo-remove-preview" data-index="' + i + '" style="width:20px;height:20px;padding:0;font-size:10px;line-height:1;">&times;</button></div>'; }); $('#kuboImagePreviews').html(h); $('#kuboPostSubmit').prop('disabled', false); },
                    error: function () { Swal.fire('Error', 'Failed to upload images.', 'error'); }
                });
            });
            $(document).on('click', '.kubo-remove-preview', function () { var idx = $(this).data('index'); uploadedImages.splice(idx, 1); $(this).parent().remove(); if (!uploadedImages.length && !$('#kuboPostContent').val().trim()) $('#kuboPostSubmit').prop('disabled', true); });
            $('#kuboPostSubmit').on('click', function () {
                var content = $('#kuboPostContent').val().trim(); if (!content && uploadedImages.length === 0) return;
                var btn = $(this); btn.prop('disabled', true).text('Posting...');
                $.ajax({ url: '/api/kubo/posts', type: 'POST', data: JSON.stringify({ content: content, images: uploadedImages, _token: self.csrf }), contentType: 'application/json',
                    success: function (res) { if (res.success) { $('#kuboPostContent').val(''); $('#kuboImagePreviews').empty(); $('#kuboImageInput').val(''); uploadedImages = []; var html = self.renderPostCard(res.post); $('#kuboFeedContainer').prepend(html); $('#createPostModal').modal('hide'); Swal.fire({ icon: 'success', title: 'Posted!', timer: 1500, showConfirmButton: false }); } },
                    error: function () { Swal.fire('Error', 'Failed to create post.', 'error'); },
                    complete: function () { btn.prop('disabled', false).text('Post'); }
                });
            });
            $(document).on('click', '.kubo-delete-post', function () {
                var postId = $(this).data('post-id'), card = $(this).closest('.kubo-post-card');
                Swal.fire({ title: 'Delete post?', text: 'This cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Delete' }).then(function (result) {
                    if (result.isConfirmed) $.ajax({ url: '/api/kubo/posts/' + parseInt(postId), type: 'DELETE', data: { _token: self.csrf }, success: function () { card.fadeOut(300, function () { $(this).remove(); }); } });
                });
            });
            $('#createPostModal').on('hidden.bs.modal', function () { $('#kuboPostContent').val(''); $('#kuboImagePreviews').empty(); $('#kuboImageInput').val(''); $('#kuboPostSubmit').prop('disabled', true); uploadedImages = []; });
        },

        // ============================================
        // REACTIONS
        // ============================================
        initReactions: function () {
            var self = this, pickerTimeout;
            $(document).on('mouseenter', '.kubo-react-btn', function () { var btn = $(this); pickerTimeout = setTimeout(function () { var picker = $('#kuboReactionPicker'), offset = btn.offset(); picker.css({ top: offset.top - 55, left: offset.left + btn.width() / 2 - 90 }); picker.removeClass('d-none').data('post-id', $(btn).data('post-id')); }, 500); });
            $(document).on('mouseleave', '.kubo-react-btn', function () { clearTimeout(pickerTimeout); setTimeout(function () { if (!$('#kuboReactionPicker:hover').length) $('#kuboReactionPicker').addClass('d-none'); }, 200); });
            $('#kuboReactionPicker').on('mouseleave', function () { $(this).addClass('d-none'); });
            $(document).on('click', '.kubo-reaction-emoji', function () { var r = $(this).data('reaction'), pid = $('#kuboReactionPicker').data('post-id'); $('#kuboReactionPicker').addClass('d-none'); if (pid) self.sendReaction(pid, r); });
            $(document).on('click', '.kubo-react-btn', function () { if ($('#kuboReactionPicker').is(':visible')) return; self.sendReaction($(this).data('post-id'), 'love'); });
        },
        sendReaction: function (postId, reactionType) {
            var self = this, card = $('.kubo-post-card[data-post-id="' + parseInt(postId) + '"]');
            $.ajax({ url: '/api/kubo/posts/' + parseInt(postId) + '/react', type: 'POST', data: JSON.stringify({ reaction_type: reactionType, _token: this.csrf }), contentType: 'application/json',
                success: function (res) { var btn = card.find('.kubo-react-btn'), count = card.find('.kubo-reactions-count'); count.text(parseInt(res.total)); if (res.current_reaction) { btn.css('color', '#ED4956'); btn.html(self.reactionEmojis[res.current_reaction] || '❤️'); } else { btn.css('color', ''); btn.html('<i class="far fa-heart"></i>'); } }
            });
        },

        // ============================================
        // COMMENTS
        // ============================================
        initComments: function () {
            var self = this;
            $(document).on('click', '.kubo-comment-toggle', function () { var pid = $(this).data('post-id'), card = $('.kubo-post-card[data-post-id="' + pid + '"]'), sec = card.find('.kubo-comments-section'); if (sec.hasClass('d-none')) { sec.removeClass('d-none'); self.loadComments(pid, card); } else sec.addClass('d-none'); });
            $(document).on('click', '.kubo-comment-submit', function () { var pid = $(this).data('post-id'), card = $('.kubo-post-card[data-post-id="' + pid + '"]'), input = card.find('.kubo-comment-input'), comment = input.val().trim(); if (!comment) return; $.ajax({ url: '/api/kubo/posts/' + pid + '/comments', type: 'POST', data: JSON.stringify({ comment: comment, _token: self.csrf }), contentType: 'application/json', success: function (res) { input.val(''); card.find('.kubo-comments-list').append(self.renderComment(res.comment)); card.find('.kubo-comments-count').text(parseInt(res.count)); } }); });
            $(document).on('keypress', '.kubo-comment-input', function (e) { if (e.which === 13) $(this).closest('.kubo-comments-section').find('.kubo-comment-submit').click(); });
            $(document).on('click', '.kubo-reply-btn', function () { var ce = $('.kubo-comment[data-comment-id="' + parseInt($(this).data('comment-id')) + '"]'); ce.find('.kubo-reply-form').toggleClass('d-none'); ce.find('.kubo-reply-input').focus(); });
            $(document).on('click', '.kubo-reply-submit', function () { var cid = $(this).data('comment-id'), ce = $('.kubo-comment[data-comment-id="' + cid + '"]'), input = ce.find('.kubo-reply-input'), reply = input.val().trim(); if (!reply) return; $.ajax({ url: '/api/kubo/comments/' + cid + '/reply', type: 'POST', data: JSON.stringify({ comment: reply, _token: self.csrf }), contentType: 'application/json', success: function (res) { input.val(''); ce.find('.kubo-replies').append('<div class="kubo-reply ms-4 mt-2 small" style="border-left:2px solid #e0e0e0;padding-left:12px;">' + self.renderReply(res.reply) + '</div>'); ce.find('.kubo-reply-form').addClass('d-none'); } }); });
        },
        loadComments: function (postId, card) { var self = this; $.get('/api/kubo/posts/' + postId + '/comments', function (res) { var list = card.find('.kubo-comments-list'); list.empty(); if (res.comments && res.comments.length > 0) $.each(res.comments, function (i, c) { list.append(self.renderComment(c)); }); else list.html('<p class="text-muted small mb-0">No comments yet.</p>'); }); },
        renderComment: function (comment) {
            var c = { id: parseInt(comment.id), comment: escHtml(comment.comment), created_at: escHtml(comment.created_at), user: { empID: escAttr(comment.user.empID), name: escHtml(comment.user.name), avatar: escUrl(comment.user.avatar) }, can_edit: !!comment.can_edit, can_delete: !!comment.can_delete, replies: [] };
            var h = '<div class="kubo-comment mb-2" data-comment-id="' + c.id + '">';
            h += '<div class="d-flex"><img src="' + c.user.avatar + '" class="rounded-circle me-2" width="28" height="28" style="object-fit:cover;" alt="Avatar">';
            h += '<div class="flex-grow-1"><small><strong>' + c.user.name + '</strong></small> <small class="text-dark">' + c.comment + '</small>';
            h += '<div class="d-flex gap-2 mt-1"><small class="text-muted" style="font-size:0.65rem;">' + c.created_at + '</small>';
            h += '<a href="javascript:void(0)" class="kubo-reply-btn text-muted small" data-comment-id="' + c.id + '" style="font-size:0.65rem;">Reply</a>';
            if (c.can_edit) h += '<a href="javascript:void(0)" class="kubo-edit-comment-btn text-muted small" data-comment-id="' + c.id + '" style="font-size:0.65rem;">Edit</a>';
            if (c.can_delete) h += '<a href="javascript:void(0)" class="kubo-delete-comment text-muted small" data-comment-id="' + c.id + '" style="font-size:0.65rem;">Delete</a>';
            h += '</div></div></div>';
            h += '<div class="kubo-edit-comment-form d-none mt-1"><div class="d-flex gap-2"><input type="text" class="form-control form-control-sm rounded-pill kubo-edit-comment-input" value="' + c.comment + '" style="font-size:0.75rem;"><button class="btn btn-sm fw-bold kubo-edit-comment-save" style="color:#008080;font-size:0.75rem;" data-comment-id="' + c.id + '">Save</button><button class="btn btn-sm text-muted kubo-edit-comment-cancel" style="font-size:0.75rem;">Cancel</button></div></div>';
            h += '<div class="kubo-reply-form d-none mt-1 ms-5"><div class="d-flex gap-2"><input type="text" class="form-control form-control-sm rounded-pill kubo-reply-input" placeholder="Write a reply..." style="font-size:0.75rem;"><button class="btn btn-sm fw-bold kubo-reply-submit" style="color:#008080;font-size:0.75rem;" data-comment-id="' + c.id + '">Reply</button></div></div>';
            if (comment.replies && comment.replies.length > 0) {
                h += '<div class="kubo-replies ms-4 mt-2" style="border-left:2px solid #e0e0e0;padding-left:12px;">';
                $.each(comment.replies, function (i, r) {
                    var rc = { id: parseInt(r.id), comment: escHtml(r.comment), created_at: escHtml(r.created_at), user: { name: escHtml(r.user.name), avatar: escUrl(r.user.avatar) }, can_delete: !!r.can_delete };
                    h += '<div class="mb-2"><div class="d-flex"><img src="' + rc.user.avatar + '" class="rounded-circle me-2" width="22" height="22" alt="Avatar"><div><small><strong>' + rc.user.name + '</strong></small> <small>' + rc.comment + '</small><div><small class="text-muted" style="font-size:0.6rem;">' + rc.created_at + '</small>';
                    if (rc.can_delete) h += ' <a href="javascript:void(0)" class="kubo-delete-comment text-muted" data-comment-id="' + rc.id + '" style="font-size:0.6rem;">Delete</a>';
                    h += '</div></div></div></div>';
                });
                h += '</div>';
            }
            h += '</div>';
            return h;
        },
        renderReply: function (reply) {
            var r = { id: parseInt(reply.id), comment: escHtml(reply.comment), created_at: escHtml(reply.created_at), user: { name: escHtml(reply.user.name), avatar: escUrl(reply.user.avatar) } };
            return '<div class="kubo-comment mb-1" data-comment-id="' + r.id + '"><div class="d-flex"><img src="' + r.user.avatar + '" class="rounded-circle me-2" width="22" height="22" alt="Avatar"><div><small><strong>' + r.user.name + '</strong></small> <small>' + r.comment + '</small><div><small class="text-muted" style="font-size:0.6rem;">' + r.created_at + '</small></div></div></div></div>';
        },
        initEditComment: function () { var self = this; $(document).on('click', '.kubo-edit-comment-btn', function () { $('.kubo-comment[data-comment-id="' + parseInt($(this).data('comment-id')) + '"]').find('.kubo-edit-comment-form').removeClass('d-none'); }); $(document).on('click', '.kubo-edit-comment-save', function () { var cid = $(this).data('comment-id'), ce = $('.kubo-comment[data-comment-id="' + cid + '"]'), t = ce.find('.kubo-edit-comment-input').val().trim(); if (!t) return; $.ajax({ url: '/api/kubo/comments/' + cid, type: 'PUT', data: JSON.stringify({ comment: t, _token: self.csrf }), contentType: 'application/json', success: function () { ce.find('.kubo-edit-comment-form').addClass('d-none'); ce.find('.text-dark').first().text(t); } }); }); $(document).on('click', '.kubo-edit-comment-cancel', function () { $(this).closest('.kubo-edit-comment-form').addClass('d-none'); }); },
        initDeleteComment: function () { var self = this; $(document).on('click', '.kubo-delete-comment', function () { var cid = $(this).data('comment-id'), el = $('.kubo-comment[data-comment-id="' + cid + '"]'); if (!confirm('Delete this comment?')) return; $.ajax({ url: '/api/kubo/comments/' + cid, type: 'DELETE', data: { _token: self.csrf }, success: function () { el.fadeOut(200, function () { $(this).remove(); }); } }); }); },

        // ============================================
        // REPOSTS
        // ============================================
        initReposts: function () { var self = this; $(document).on('click', '.kubo-repost-btn', function () { var pid = $(this).data('post-id'), btn = $(this), card = $('.kubo-post-card[data-post-id="' + pid + '"]'); $.ajax({ url: '/api/kubo/posts/' + pid + '/repost', type: 'POST', data: { _token: self.csrf }, success: function (res) { if (res.action === 'added') btn.css('color', '#008080'); else btn.css('color', ''); card.find('.kubo-reposts-count').text(parseInt(res.count)); } }); }); },

        // ============================================
        // NOTIFICATIONS
        // ============================================
        initNotifications: function () { var self = this; this.updateNotificationBadge(); setInterval(function () { self.updateNotificationBadge(); }, 60000); if ($('#kuboNotificationsList').length) this.loadNotifications(); $('#kuboMarkAllRead').on('click', function () { $.post('/api/kubo/notifications/read', { _token: self.csrf }, function () { $('#kuboNotificationsList .card').removeClass('bg-light'); $('#kuboNotificationBadge').addClass('d-none'); }); }); },
        updateNotificationBadge: function () { $.get('/api/kubo/notifications/count', function (res) { var b = $('#kuboNotificationBadge'); if (parseInt(res.count) > 0) b.text(parseInt(res.count)).removeClass('d-none'); else b.addClass('d-none'); }); },
        loadNotifications: function () { var self = this; $.get('/api/kubo/notifications', function (res) { if (!res.notifications || !res.notifications.length) { $('#notificationsEmpty').removeClass('d-none'); return; } var list = $('#kuboNotificationsList'); $.each(res.notifications, function (i, n) { var bg = n.is_read ? '' : 'bg-light'; var h = '<div class="card border-0 shadow-sm mb-2 ' + bg + '" style="border-radius:12px;">'; h += '<div class="card-body p-3 d-flex align-items-center">'; if (n.actor) h += '<img src="' + escUrl(n.actor.avatar) + '" class="rounded-circle me-3" width="40" height="40" alt="Avatar">'; else h += '<div class="me-3 rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width:40px;height:40px;"><i class="fas fa-bell text-white"></i></div>'; h += '<div class="flex-grow-1"><small>' + escHtml(n.message) + '</small><br><small class="text-muted" style="font-size:0.65rem;">' + escHtml(n.created_at) + '</small></div></div></div>'; list.append(h); }); }); },

        // ============================================
        // EXPLORE
        // ============================================
        initExplore: function () { var self = this; if (!$('#exploreTabs').length) return; this.loadExploreTab('trending', 'trendingGrid', 'trendingLoader'); $('#exploreTabs button').on('shown.bs.tab', function () { var t = $(this).data('tab'); if (!$('#' + t + 'Grid').children().length) self.loadExploreTab(t, t + 'Grid', t + 'Loader'); }); },
        loadExploreTab: function (tab, gridId, loaderId) { var self = this; $('#' + loaderId).removeClass('d-none'); $.get('/api/kubo/explore/' + tab, function (res) { $('#' + loaderId).addClass('d-none'); var g = $('#' + gridId); if (res.posts && res.posts.length) $.each(res.posts, function (i, p) { var img = p.images && p.images.length > 0 ? escUrl(p.images[0].url) : ''; var h = '<div class="col-4 col-md-3"><div class="card border-0 shadow-sm" style="border-radius:10px;overflow:hidden;">'; if (img) h += '<img src="' + img + '" class="card-img-top" style="height:180px;object-fit:cover;" alt="Post">'; h += '<div class="card-body p-2"><small class="text-dark d-block text-truncate" style="font-size:0.75rem;">' + escHtml((p.content || '').substring(0, 60)) + '</small>'; h += '<div class="d-flex justify-content-between"><small class="text-muted" style="font-size:0.65rem;">❤️ ' + (parseInt(p.reactions ? p.reactions.total : 0) || 0) + '</small><small class="text-muted" style="font-size:0.65rem;">💬 ' + (parseInt(p.comments_count || 0) || 0) + '</small></div></div></div></div>'; g.append(h); }); else g.html('<p class="text-muted text-center w-100 py-4">No posts yet.</p>'); }); },

        // ============================================
        // PROFILE
        // ============================================
        initProfile: function () { var self = this; if (!$('#profileTabs').length) return; var uid = $('#profileStats').data('user-id'); var prefix = uid ? uid + '/' : ''; $.get('/api/kubo/profile/' + prefix + 'stats', function (res) { $('#statPosts').text(parseInt(res.total_posts || 0)); $('#statReactions').text(parseInt(res.total_reactions_received || 0)); $('#statComments').text(parseInt(res.total_comments || 0)); }); this.loadProfileTab('posts'); $('#profileTabs button').on('click', function () { $('#profileTabs button').removeClass('active'); $(this).addClass('active'); self.loadProfileTab($(this).data('tab')); }); },
        loadProfileTab: function (tab) { $('#profileLoader').removeClass('d-none'); $('#profileTabContent').empty(); var uid = $('#profileStats').data('user-id'); var prefix = uid ? uid + '/' : ''; var url = '/api/kubo/profile/' + prefix + tab; $.get(url, function (res) { $('#profileLoader').addClass('d-none'); var c = $('#profileTabContent'); if (tab === 'photos' && res.photos) $.each(res.photos, function (i, p) { c.append('<div class="col-4"><div class="card border-0 shadow-sm"><img src="' + escUrl(p.url) + '" class="kubo-lightbox-trigger" style="width:100%;height:160px;object-fit:cover;cursor:pointer;" alt="Photo"></div></div>'); }); else if (res.posts && res.posts.length) $.each(res.posts, function (i, p) { var img = p.images && p.images.length > 0 ? escUrl(p.images[0].url) : ''; c.append('<div class="col-4"><div class="card border-0 shadow-sm">' + (img ? '<img src="' + img + '" class="kubo-lightbox-trigger" style="width:100%;height:160px;object-fit:cover;cursor:pointer;" alt="Post">' : '') + '<div class="card-body p-2 text-center"><small>❤️ ' + (parseInt(p.reactions ? p.reactions.total : 0) || 0) + ' 💬 ' + (parseInt(p.comments_count || 0) || 0) + '</small></div></div></div>'); }); else c.html('<p class="text-muted text-center w-100 py-4">Nothing here yet.</p>'); }); },

        // ============================================
        // LIGHTBOX
        // ============================================
        initLightbox: function () { $(document).on('click', '.kubo-lightbox-trigger', function () { Swal.fire({ imageUrl: $(this).attr('src'), showConfirmButton: false, showCloseButton: true, width: 'auto', background: '#000' }); }); }
    };

    $(document).ready(function () { KuBo.init(); KuBo.initEditComment(); KuBo.initDeleteComment(); });
})(jQuery, window);