<?php namespace App\Models; use Illuminate\Database\Eloquent\Model;

class KuBoPresence extends Model {
    protected $table = 'kubo_presences';
    protected $fillable = ['user_id', 'last_seen_at'];
    protected $casts = ['last_seen_at' => 'datetime'];

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'empID');
    }
}
