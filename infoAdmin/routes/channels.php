<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal público para broadcast de mensajes a todos los clientes MensaDesk
Broadcast::channel('mensajes', function () {
    return true;
});
