<?php

namespace App\Http\Controllers;

use App\Models\Module4Notification;
use App\Services\Module4NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class Module4NotificationController extends Controller
{
    public function __construct(private Module4NotificationService $service) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['canal', 'lue']);
        return view('front.module4_notifications.index', [
            'notifications' => $this->service->forUser($request->user(), $filters),
            'filters' => $filters,
            'canaux' => Module4Notification::CANAUX,
        ]);
    }

    public function read(Request $request, Module4Notification $notification): RedirectResponse
    {
        $this->service->markAsRead($request->user(), $notification);
        return back()->with('success', 'La notification a été marquée comme lue.');
    }

    public function generate(Request $request): RedirectResponse
    {
        $count = $this->service->generateForUser($request->user());
        return back()->with('success', $count
            ? "{$count} notification(s) de prévention ont été générées."
            : 'Aucune nouvelle notification de prévention n’est disponible.');
    }
}
