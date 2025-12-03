<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
	use HasFactory;

	protected $fillable = [
		'chat_topic_id',
		'user_id',
		'role',
		'content',
	];

	public function topic(): BelongsTo
	{
		return $this->belongsTo(ChatTopic::class, 'chat_topic_id');
	}

	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}
}
