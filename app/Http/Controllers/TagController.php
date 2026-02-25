<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Tag;

class TagController extends Controller
{
    public function toggle(Request $request, Message $message)
    {
        $tenant = auth()->user()->tenant;
        if (!$tenant || $message->mailbox->tenant_id !== $tenant->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'color' => 'nullable|string|max:20',
        ]);

        $tag = $tenant->tags()->firstOrCreate(
            ['name' => strtolower(trim($validated['name']))],
            ['color' => $validated['color'] ?? '#64748b'] // Default slate-500
        );

        $message->tags()->toggle($tag->id);

        return back()->with('success', 'Tags updated.');
    }
}
