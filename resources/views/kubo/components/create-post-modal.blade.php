<div class="modal fade" id="createPostModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <h6 class="modal-title fw-bold">Create Post</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 pb-2">
                <div class="d-flex align-items-start mb-2">
                    <img src="{{ auth()->user()->community_avatar }}" class="rounded-circle me-3 flex-shrink-0" width="40" height="40" style="object-fit: cover;">
                    <div class="w-100">
                        {{-- Author + audience selector --}}
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <small class="fw-bold">{{ auth()->user()->community_full_name }}</small>
                            <select id="kuboPostVisibility" class="form-select form-select-sm rounded-pill border-0" style="width:auto;font-size:0.72rem;color:#555;background:#f2f2f2;padding:2px 28px 2px 10px;">
                                <option value="public">🌐 Everyone</option>
                                <option value="connections_only">🏢 My Department</option>
                            </select>
                        </div>
                        {{-- Textarea --}}
                        <textarea id="kuboPostContent" class="form-control border-0 mt-1" rows="4"
                            placeholder="What's on your mind?" style="resize: none; font-size: 0.9rem;" maxlength="1000"></textarea>
                        {{-- Character counter --}}
                        <div class="d-flex justify-content-end mt-1">
                            <small id="kuboCharCount" class="text-muted" style="font-size: 0.7rem;">0/1000</small>
                        </div>
                    </div>
                </div>

                {{-- Hashtag suggestions dropdown --}}
                <div id="kuboHashtagSuggest" class="d-none mb-2" style="background:#fff;border:1px solid #e0e0e0;border-radius:10px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.08);"></div>

                {{-- Emoji picker panel --}}
                <div id="kuboEmojiPanel" class="d-none mb-2 p-2" style="background:#f8f9fa;border:1px solid #e0e0e0;border-radius:10px;max-height:160px;overflow-y:auto;">
                    <div style="display:grid;grid-template-columns:repeat(10,1fr);gap:1px;">
                        @foreach(['😀','😂','🥹','😍','🤩','😎','🥳','🤗','🫶','❤️','🔥','✨','👍','👏','🎉','🙏','💪','🫡','😅','🤔','😮','😢','😤','🤝','👋','💯','🚀','⭐','🏆','💡','📢','💬','🎯','👀','✅','⚡','🌟','💼','📊','🍀'] as $emoji)
                        <span class="kubo-emoji-btn" title="{{ $emoji }}"
                            style="cursor:pointer;font-size:1.25rem;text-align:center;padding:4px 2px;border-radius:5px;line-height:1.5;transition:transform 0.1s,background 0.1s;">{{ $emoji }}</span>
                        @endforeach
                    </div>
                </div>

                {{-- Image previews --}}
                <div id="kuboImagePreviews" class="d-flex flex-wrap gap-2 mb-2"></div>

                {{-- Toolbar + actions --}}
                <div class="d-flex align-items-center justify-content-between border-top pt-3">
                    <div class="d-flex gap-2">
                        <label class="btn btn-light btn-sm rounded-pill" style="background: #f2f2f2;">
                            <i class="far fa-image text-success me-1"></i> Photo
                            <input type="file" id="kuboImageInput" class="d-none" accept="image/*" multiple>
                        </label>
                        <button type="button" id="kuboEmojiToggle" class="btn btn-light btn-sm rounded-pill" style="background: #f2f2f2;">
                            😊 Emoji
                        </button>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-light btn-sm rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="kuboPostSubmit" class="btn btn-sm rounded-pill px-4 text-white" style="background: #008080;" disabled>Post</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-3">
                <small class="text-muted w-100 text-start" style="font-size: 0.7rem;">
                    Tip: Use <strong>#hashtags</strong> to categorize your post
                </small>
            </div>
        </div>
    </div>
</div>

<style>
    .kubo-emoji-btn:hover { transform: scale(1.35); background: #e8f5f5; }
</style>
