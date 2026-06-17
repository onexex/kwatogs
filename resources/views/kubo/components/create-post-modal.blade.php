<div class="modal fade" id="createPostModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <h6 class="modal-title fw-bold">Create Post</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 pb-2">
                <div class="d-flex align-items-start mb-3">
                    <img src="{{ auth()->user()->community_avatar }}" class="rounded-circle me-3" width="40" height="40" style="object-fit: cover;">
                    <div class="w-100">
                        <small class="fw-bold d-block">{{ auth()->user()->community_full_name }}</small>
                        <textarea id="kuboPostContent" class="form-control border-0 mt-2" rows="4" placeholder="What's on your mind?" style="resize: none; font-size: 0.9rem;"></textarea>
                    </div>
                </div>
                <div id="kuboImagePreviews" class="d-flex flex-wrap gap-2 mb-2"></div>
                <div class="d-flex align-items-center justify-content-between border-top pt-3">
                    <label class="btn btn-light btn-sm rounded-pill" style="background: #f2f2f2;">
                        <i class="far fa-image text-success me-1"></i> Add Photo
                        <input type="file" id="kuboImageInput" class="d-none" accept="image/*" multiple>
                    </label>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light btn-sm rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="kuboPostSubmit" class="btn btn-sm rounded-pill px-4 text-white" style="background: #008080;" disabled>Post</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 px-4">
                <small class="text-muted w-100 text-start" style="font-size: 0.7rem;">Tip: Use <strong>#hashtags</strong> to categorize your post</small>
            </div>
        </div>
    </div>
</div>