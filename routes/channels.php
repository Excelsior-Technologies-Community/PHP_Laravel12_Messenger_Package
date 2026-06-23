<?php

use Illuminate\Support\Facades\Broadcast;
use RTippin\Messenger\Broadcasting\ProviderChannel;
use RTippin\Messenger\Broadcasting\ThreadChannel;
use RTippin\Messenger\Broadcasting\CallChannel;

Broadcast::channel('messenger.{alias}.{id}', ProviderChannel::class);

Broadcast::channel('messenger.thread.{thread}', ThreadChannel::class);

Broadcast::channel('messenger.call.{call}.thread.{thread}', CallChannel::class);