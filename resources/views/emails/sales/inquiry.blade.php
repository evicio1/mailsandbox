<x-mail::message>
# New Premium Plan Inquiry

A new tenant has requested to talk to sales about upgrading to the **Premium** plan!

**Tenant Details:**
- **Workspace:** {{ $inquiry->tenant->name }} (ID: {{ $inquiry->tenant_id }})
- **Current Plan:** {{ ucfirst($inquiry->current_plan) }}
- **Requested Plan:** {{ ucfirst($inquiry->requested_plan) }}

**Contact Info:**
- **User:** {{ $inquiry->user->name }}
- **Email:** {{ $inquiry->user->email }}

<x-mail::button :url="url('/admin/tenants/' . $inquiry->tenant_id)">
View Tenant in Admin
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
