<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('channel.{channelId}', function ($user, $channelId) {
    // Logic to verify if the user is a member of the channel
    return $user->channels()->where('channel_id', $channelId)->exists();
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    // Logic to verify if the user can receive private messages
    return (int) $user->id === (int) $userId;
});
