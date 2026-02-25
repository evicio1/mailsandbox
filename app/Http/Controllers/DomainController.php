<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DomainController extends Controller
{
    public function index()
    {
        $tenant = auth()->user()->tenant;
        $domains = $tenant->domains()->latest()->get();

        return view('domains.index', compact('tenant', 'domains'));
    }

    public function create()
    {
        return view('domains.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'domain' => ['required', 'string', 'max:255', 'unique:domains,domain', 'regex:/^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/']
        ], [
            'domain.regex' => 'The domain format is invalid.'
        ]);

        $tenant = auth()->user()->tenant;

        $tenant->domains()->create([
            'domain' => strtolower($request->domain),
            'verification_token' => 'maileyez-verify=' . Str::random(32),
            'is_verified' => false,
            'is_platform_provided' => false,
            'catch_all_enabled' => false,
        ]);

        return redirect()->route('domains.index')->with('success', 'Domain added. Please verify ownership.');
    }

    public function edit(Domain $domain)
    {
        // Ensure tenant owns the domain
        if ($domain->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        return view('domains.edit', compact('domain'));
    }

    public function update(Request $request, Domain $domain)
    {
        if ($domain->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $domain->update([
            'catch_all_enabled' => $request->has('catch_all_enabled')
        ]);

        return redirect()->route('domains.index')->with('success', 'Domain settings updated.');
    }

    public function destroy(Domain $domain)
    {
        if ($domain->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $domain->delete();

        return redirect()->route('domains.index')->with('success', 'Domain removed.');
    }

    public function verify(Domain $domain)
    {
        if ($domain->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if ($domain->is_verified) {
            return redirect()->back()->with('info', 'Domain is already verified.');
        }

        $token = $domain->verification_token;
        $records = @dns_get_record($domain->domain, DNS_TXT);
        
        $verified = false;
        if ($records) {
            foreach ($records as $record) {
                if (isset($record['txt']) && str_contains($record['txt'], $token)) {
                    $verified = true;
                    break;
                }
            }
        }

        if ($verified) {
            $domain->update(['is_verified' => true]);
            return redirect()->back()->with('success', 'Domain ownership verified successfully!');
        }

        return redirect()->back()->with('error', 'Verification failed. We could not find the TXT record. DNS propagation may take some time.');
    }
}
