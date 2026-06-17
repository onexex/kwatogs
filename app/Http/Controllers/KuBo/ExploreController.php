<?php namespace App\Http\Controllers\KuBo; use App\Http\Controllers\Controller; use App\Models\CommunityPost; use Illuminate\Http\JsonResponse;

class ExploreController extends Controller {
    public function trending(): JsonResponse { $p = CommunityPost::feed()->where('created_at','>=',now()->subDays(7))->orderBy('reactions_count','desc')->paginate(12); return response()->json($this->fmt($p)); }
    public function popular(): JsonResponse { $p = CommunityPost::feed()->orderBy('reactions_count','desc')->paginate(12); return response()->json($this->fmt($p)); }
    public function photos(): JsonResponse { $p = CommunityPost::feed()->whereHas('images')->orderBy('created_at','desc')->paginate(12); return response()->json($this->fmt($p)); }
    private function fmt($p): array { return ['posts'=>$p->map(fn($x)=>['id'=>$x->id,'content'=>$x->content,'created_at'=>$x->created_at->diffForHumans(),'user'=>['empID'=>$x->user->empID??'','name'=>$x->user->community_full_name,'avatar'=>$x->user->community_avatar],'images'=>$x->images->map(fn($i)=>['id'=>$i->id,'url'=>asset($i->image_path)]),'reactions'=>['total'=>$x->reactions_count??0],'comments_count'=>$x->comments_count??0]),'has_more'=>$p->hasMorePages()]; }
}