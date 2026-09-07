<div class="row-actions">
    <a href="{{ route('dashboard.facilitators.edit', $facilitator->id) }}" title="Edit" aria-label="Edit"
       data-modal-open data-modal-url="{{ route('dashboard.facilitators.edit', $facilitator->id) }}" data-modal-title="Edit Facilitator"><i class="fa-solid fa-pen-to-square"></i></a>

    <form method="POST" action="{{ route('dashboard.facilitators.credentials', $facilitator->id) }}"
          data-confirm="{{ $facilitator->must_change_password
              ? 'Send '.$facilitator->name.' their login again? They haven\'t set their own password yet, so a new temporary one is generated and the old one stops working.'
              : 'Send '.$facilitator->name.' a sign-in reminder? They chose their own password, so it stays as it is.' }}">
        @csrf
        <button type="submit" title="{{ $facilitator->credentials_sent_at ? 'Resend login details' : 'Send login details' }}"
                aria-label="{{ $facilitator->credentials_sent_at ? 'Resend login details' : 'Send login details' }}">
            <i class="fa-solid fa-paper-plane"></i>
        </button>
    </form>

    {{-- Revoke, not delete: everything they taught in the portal points at this
         account, and it may be an admin's too. --}}
    <form method="POST" action="{{ route('dashboard.facilitators.revoke', $facilitator->id) }}"
          data-confirm="Take facilitator access off {{ $facilitator->name }}? They lose the instructor side of the portal. Their account, classes and marking are all kept.">
        @csrf
        @method('DELETE')
        <button type="submit" class="link-danger" title="Revoke facilitator access" aria-label="Revoke facilitator access"><i class="fa-solid fa-user-slash"></i></button>
    </form>
</div>
