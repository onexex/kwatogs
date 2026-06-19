<?php namespace App\Models; use Illuminate\Database\Eloquent\Model;

class KuBoMessage extends Model {
    protected $table = 'kubo_messages';
    protected $fillable = ['sender_id', 'receiver_id', 'message', 'is_read'];

    public function sender() {
        return $this->belongsTo(User::class, 'sender_id', 'empID');
    }
    public function receiver() {
        return $this->belongsTo(User::class, 'receiver_id', 'empID');
    }
}