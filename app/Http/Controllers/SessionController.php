<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SessionController extends Controller
{
    /**
     * List all active sessions for the authenticated user.
     */
    public function index(Request $request)
    {
        $sessions = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($session) use ($request) {
                return (object) [
                    'id'              => $session->id,
                    'ip_address'      => $session->ip_address,
                    'user_agent'      => $session->user_agent,
                    'last_active'     => \Carbon\Carbon::createFromTimestamp($session->last_activity),
                    'is_current'      => $session->id === $request->session()->getId(),
                ];
            });

        return view('profile.sessions', compact('sessions'));
    }

    /**
     * Revoke (delete) a specific session.
     */
    public function destroy(Request $request, string $sessionId)
    {
        // Users can only revoke their own sessions
        $deleted = DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $request->user()->id)
            ->delete();

        if (! $deleted) {
            abort(403, 'Session not found or unauthorized.');
        }

        return back()->with('success', 'Session revoked successfully.');
    }
}
