# PHP_Laravel12_Messenger_Package

## Introduction

PHP_Laravel12_Messenger_Package is a demonstration project built using Laravel 12 that integrates a full-featured messaging system with real-time capabilities. The project leverages the RTippin Messenger package, which provides robust messaging functionality for Laravel applications.

With this system, users can:

- Send direct messages between individual users

- Participate in group conversations

- Attach files and media to messages

- View read receipts and message reactions

- Manage threads and participants

- Experience real-time messaging with typing indicators

This project is designed to show step-by-step how to install, configure, and use the Messenger package in a Laravel 12 application. It provides a working example of a secure, user-based chat system integrated seamlessly with Laravel’s authentication.

---

## Project Overview

The PHP_Laravel12_Messenger_Package project demonstrates a complete messaging workflow for Laravel applications:

1) User Authentication – Users can register and log in using Laravel Breeze.

2) Messenger Installation – The RTippin Messenger package is installed and configured, providing database tables, models, and services needed for messaging.

3) User Model Integration – The User model implements the MessengerProvider interface and uses the Messageable trait to send and receive messages.

4) Controller & Routes – MessengerController handles displaying threads and sending messages, while routes define accessible endpoints for the chat system.

5) Frontend UI – A simple Blade-based interface allows users to send messages and view conversation threads.

6) Navigation & Testing – The Messenger link is integrated into the main navigation, and the chat system can be tested with multiple users.

This project is ideal for developers looking to add a fully functional messaging feature to a Laravel 12 application without building it from scratch.

---

## Project Requirements

Before starting the project, make sure the following tools are installed:

- PHP 8.2 or higher

- Composer

- Node.js & NPM

- MySQL

- Laravel CLI

- XAMPP / Laragon

---

## Step 1 — Create Laravel 12 Project

Open terminal and run:

```bash
composer create-project laravel/laravel PHP_Laravel12_Messenger_Package "12.*"
```

Move inside the project:

```bash
cd PHP_Laravel12_Messenger_Package
```

Start the development server:

```bash
php artisan serve
```

Open in browser:

```bash
http://127.0.0.1:8000
```

---

## Step 2 — Configure Database

Open the .env file.

Update the database configuration:

```.env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel12_messenger
DB_USERNAME=root
DB_PASSWORD=
```

Run Migration Command:

```bash
php artisan migrate
```

---

## Step 3 — Install Authentication (Laravel Breeze)

The Messenger system requires authenticated users.

Install Laravel Breeze:

```bash
composer require laravel/breeze --dev
```

Install Breeze scaffolding:

```bash
php artisan breeze:install blade
```
Install frontend dependencies:

```bash
npm install
npm run build
```

Run migrations:

```bash
php artisan migrate
```

Now you can register and login users.

---

## Step 4 — Install RTippin Messenger Package

Install the Messenger package using Composer:

```bash
composer require rtippin/messenger
```

This installs the complete messaging system for Laravel.

---

## Step 5 — Install Messenger Core Files

Run the messenger installer command:

```bash
php artisan messenger:install
```
This command automatically performs several setup tasks:

- Publishes the messenger.php configuration file

- Publishes messenger database migrations

- Registers the MessengerServiceProvider

- Optionally runs migrations

---

## Step 6 — Run Database Migrations

If you did not run migrations during the installation step, run them manually:

```bash
php artisan migrate
```
This will create all Messenger-related tables in your database.

These new tables:

```
friends
pending_friends
threads
participants
messages
calls
call_participants
message_edits
thread_invites
messengers
message_reactions
bots
bot_actions
```

---

## Step 7 — Register Messenger Providers

Messenger uses Providers to determine which models can send and receive messages.

Most Laravel applications only register the User model.

Open:

```bash
app/Providers/MessengerServiceProvider.php
```
Update the file as follows:

```php
<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Facades\MessengerBots;

/**
 * Laravel Messenger System, Created by: Richard Tippin.
 * @link https://github.com/RTippin/messenger
 * @link https://github.com/RTippin/messenger-bots
 * @link https://github.com/RTippin/messenger-faker
 * @link https://github.com/RTippin/messenger-ui
 */
class MessengerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Register all provider models you wish to use in messenger.
        Messenger::registerProviders([
            User::class,
        ]);

        // Set the video call driver of your choosing.
        // Messenger::setVideoDriver(MyVideoBroker::class);

        // Register bot handlers you wish to use. You can install the messenger-bots addon for ready-made handlers.
        MessengerBots::registerHandlers([
            //
        ]);

        // Register the packaged bots you wish to use.
        MessengerBots::registerPackagedBots([
            //
        ]);
    }
}
```

This tells the Messenger system that the User model is allowed to send and receive messages.

---

## Step 8 — Update the User Model

Open:

```bash
app/Models/User.php
```

Update the model to implement the MessengerProvider interface:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// Messenger Imports
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Traits\Messageable;

class User extends Authenticatable implements MessengerProvider
{
    use HasFactory, Notifiable, Messageable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Messenger Provider Settings
     */
    public static function getProviderSettings(): array
    {
        return [
            'alias' => 'user',
        ];
    }
}
```

This enables the messaging functionality for users.

---

## Step 9 — Attach Messenger to Existing Users

After updating your User model, any existing users in your database do not yet have a Messenger instance. You need to attach Messenger manually:

Run this command:

```bash
php artisan messenger:attach:messengers
```

---

## Step 10 — Create a Messenger Controller

Run:

```bash
php artisan make:controller MessengerController
```

Open:

```bash
app/Http/Controllers/MessengerController.php
```


Add this code:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use RTippin\Messenger\Facades\MessengerComposer;
use RTippin\Messenger\Models\Thread; //  Import this

class MessengerController extends Controller
{
public function index()
{
    $user = auth()->user();

    // Eager load the latestMessage relation
    $threads = Thread::whereHas('participants', function ($query) use ($user) {
        $query->where('owner_id', $user->id)
              ->where('owner_type', get_class($user));
    })->with('latestMessage') //  eager load last message
      ->latest('updated_at')
      ->get();

    return view('messenger.index', compact('threads'));
}

  public function sendMessage(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'message' => 'required',
    ]);

    $sender = auth()->user();
    $receiver = User::findOrFail($request->user_id);

    // Use MessengerComposer to send a private message
    MessengerComposer::to($receiver)
        ->from($sender)
        ->message($request->message);

    return back()->with('success', 'Message sent successfully!');
}
}
```

---

## Step 11 — Create Routes

Open:

routes/web.php

Add:

```php
use App\Http\Controllers\MessengerController;

Route::middleware(['auth'])->group(function () {

    Route::get('/messenger', [MessengerController::class, 'index'])->name('messenger');

    Route::post('/send-message', [MessengerController::class, 'sendMessage'])->name('send.message');

});
```

---

## Step 12 — Create Messenger View

Create folder:

```bash
resources/views/messenger
```

Create file:

```bash
resources/views/messenger/index.blade.php
```

Add this simple UI:

```blade
<x-app-layout>

    <div class="p-6">

        <h2 class="text-xl font-bold mb-4">Messenger</h2>

        @if(session('success'))
        <div class="mb-4 text-green-600">
            {{ session('success') }}
        </div>
        @endif

        <h3 class="font-semibold mb-2">Send Message</h3>

        <form method="POST" action="{{ route('send.message') }}">
            @csrf

            <div class="mb-2">
                <label>User ID</label>
                <input type="number" name="user_id" class="border p-2 w-full">
            </div>

            <div class="mb-2">
                <label>Message</label>
                <textarea name="message" class="border p-2 w-full"></textarea>
            </div>

            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
                Send Message
            </button>
        </form>

        <hr class="my-6">

        <h3 class="font-semibold mb-2">Your Threads</h3>

        @forelse($threads as $thread)
        <div class="border p-3 mb-2">
            Thread ID: {{ $thread->id }} <br>
            Last Message: {{ $thread->latestMessage?->body ?? 'No messages yet' }}
            <br>
            Sent At: {{ $thread->latestMessage?->created_at?->format('d M Y H:i') }}
        </div>
        @empty
        <p class="text-gray-500">No conversations yet.</p>
        @endforelse

    </div>

</x-app-layout>
```

---

## Step 13 — Add Navigation Link

Open:

```bash
resources/views/layouts/navigation.blade.php
```

Add Messanger:

```blade
<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    <x-nav-link :href="route('messenger')" :active="request()->routeIs('messenger')">
                        {{ __('Messenger') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
```

---

## Step 14 — Test the Chat System

### Terminal 1 — Run Laravel Development Server:

```bash
php artisan serve
```

### Terminal 2 — Run Frontend Asset Compilation (Vite/NPM):

```bash
npm run dev
```
> Keep both terminals running while testing.

Register two users:

```bash
http://127.0.0.1:8000/register
```

Example:

| User  | ID |
| ----- | -- |
| User1 | 1  |
| User2 | 2  |

Login as User1.

Open:

```bash
http://127.0.0.1:8000/messenger
```
Send message:

```bash
User ID: 2
Message: Hello
```

---

## Output

### First User

<img width="1919" height="1028" alt="Screenshot 2026-03-10 115547" src="https://github.com/user-attachments/assets/8075b2d0-f35b-405b-b897-f897da170196" />

### Second User

<img width="1919" height="1027" alt="Screenshot 2026-03-10 120839" src="https://github.com/user-attachments/assets/ef4c89fe-6297-43d4-86c5-aec1e883e3d8" />

### Message Through Second User

<img width="1919" height="1027" alt="Screenshot 2026-03-10 142400" src="https://github.com/user-attachments/assets/6eb2aead-af8b-4fa9-aab2-9161e12a6e32" />

<img width="1919" height="1028" alt="Screenshot 2026-03-10 142425" src="https://github.com/user-attachments/assets/c741085d-58fb-4830-9435-2fff7100a319" />

### Check Message In First User

<img width="1919" height="1027" alt="Screenshot 2026-03-10 142459" src="https://github.com/user-attachments/assets/5e53449b-7529-448f-b664-124e65f5f2d8" />

---

## Project Structure

```
PHP_Laravel12_Messenger_Package/
├── app/
│   ├── Console/
│   ├── Exceptions/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── MessengerController.php       # Handles messenger index & sendMessage
│   ├── Models/
│   │   └── User.php                          # Implements MessengerProvider, uses Messageable trait
│   ├── Providers/
│   │   └── MessengerServiceProvider.php     # Registers Messenger providers and bots
│   └── ...
├── bootstrap/
│   └── app.php
├── config/
│   └── messenger.php                         # Messenger configuration
├── database/
│   ├── migrations/
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│       ├── layouts/
│       │   └── navigation.blade.php          # Navbar with Messenger link
│       ├── messenger/
│       │   └── index.blade.php               # Messenger chat UI
│       ├── dashboard.blade.php
│       └── ...
├── routes/
│   └── web.php                               # Routes for /messenger and /send-message
├── vendor/
├── .env                                      # Database config + other env settings
├── artisan
├── composer.json
└── package.json
```

---

Your PHP_Laravel12_Messenger_Package Project is now ready!




