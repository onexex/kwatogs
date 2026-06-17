@extends('kubo.layout.kubo')
@section('kubo-content')
<div class="container" style="max-width: 935px;">
    <div class="text-center mb-4"><h5 class="fw-bold text-dark mb-1"><i class="far fa-compass me-2"></i>Explore</h5><p class="text-muted small">Discover trending content from the community</p></div>
    <ul class="nav nav-pills justify-content-center mb-4" id="exploreTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active rounded-pill px-4" data-bs-toggle="tab" data-bs-target="#trendingTab" data-tab="trending"><i class="fas fa-fire me-1"></i> Trending</button></li>
        <li class="nav-item"><button class="nav-link rounded-pill px-4" data-bs-toggle="tab" data-bs-target="#popularTab" data-tab="popular"><i class="fas fa-star me-1"></i> Most Popular</button></li>
        <li class="nav-item"><button class="nav-link rounded-pill px-4" data-bs-toggle="tab" data-bs-target="#photosTab" data-tab="photos"><i class="fas fa-images me-1"></i> Photos</button></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="trendingTab"><div class="row g-3 kubo-explore-grid" id="trendingGrid"></div><div id="trendingLoader" class="text-center py-4 d-none"><div class="spinner-border text-muted"></div></div></div>
        <div class="tab-pane fade" id="popularTab"><div class="row g-3 kubo-explore-grid" id="popularGrid"></div><div id="popularLoader" class="text-center py-4 d-none"><div class="spinner-border text-muted"></div></div></div>
        <div class="tab-pane fade" id="photosTab"><div class="row g-3 kubo-explore-grid" id="photosGrid"></div><div id="photosLoader" class="text-center py-4 d-none"><div class="spinner-border text-muted"></div></div></div>
    </div>
</div>
@endsection