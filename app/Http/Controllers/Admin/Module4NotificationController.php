<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreModule4NotificationRequest;
use App\Models\Module4Notification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class Module4NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = Module4Notification::with(['user', 'equipement'])
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('canal'), fn ($query) => $query->where('canal', $request->string('canal')))
            ->when($request->filled('lue'), fn ($query) => $query->where('lue', $request->boolean('lue')))
            ->latest('date_envoi')
            ->paginate(15)
            ->withQueryString();

        return view('admin.module4_notifications.index', [
            'notifications' => $notifications,
            'users' => User::orderBy('nom')->get(),
            'filters' => $request->only(['user_id', 'canal', 'lue']),
            'canaux' => Module4Notification::CANAUX,
        ]);
    }

    public function create(): View
    {
        return view('admin.module4_notifications.create', [
            'users' => User::where('role', User::ROLE_USER)->orderBy('nom')->get(),
            'equipements' => \App\Models\EquipementSensible::with('user')->latest()->get(),
            'canaux' => Module4Notification::CANAUX,
        ]);
    }

    public function store(StoreModule4NotificationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        Module4Notification::create($data + [
            'date_envoi' => now(),
            'lue' => false,
            'deduplication_key' => 'manual:' . \Illuminate\Support\Str::uuid(),
        ]);

        return redirect()->route('admin.module4_notifications.index')
            ->with('success', 'La notification de prévention a été créée.');
    }
}
