<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MessengerController;

// Public route
Route::get('/', function () {
    return view('welcome');
});

// Auth routes group
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/messenger', [MessengerController::class, 'index'])->name('messenger');
    
    // Send message (form submit)
    Route::post('/messenger/send', [MessengerController::class, 'sendMessage'])->name('send.message');
    
    // AJAX Send message
    Route::post('/messenger/send-ajax', [MessengerController::class, 'sendMessageAjax'])->name('send.message.ajax');
    
    // Get messages for thread
    Route::get('/messenger/messages/{threadId}', [MessengerController::class, 'getMessages'])->name('messenger.messages');
    
    // Get or create thread
    Route::post('/messenger/thread', [MessengerController::class, 'getOrCreateThread'])->name('messenger.thread');
    
    // Mark as read
    Route::post('/messenger/mark-read', [MessengerController::class, 'markAsRead'])->name('messenger.mark.read');
    
    // Delete message
    Route::delete('/messenger/message/{messageId}', [MessengerController::class, 'deleteMessage'])->name('messenger.delete');
    
    // Search users
    Route::get('/messenger/search-users', [MessengerController::class, 'searchUsers'])->name('messenger.search.users');
    
    // Typing indicator
    Route::post('/messenger/typing', [MessengerController::class, 'typingIndicator'])->name('messenger.typing');
    
    // Upload attachment
    Route::post('/messenger/upload', [MessengerController::class, 'uploadAttachment'])->name('messenger.upload');


    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});

require __DIR__ . '/auth.php';