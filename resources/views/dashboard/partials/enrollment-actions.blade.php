<div class="row-actions">
    @if ($enrollment->is_completed)
        <form method="POST" action="{{ route('dashboard.course-registrations.credentials', $enrollment) }}"
              data-confirm="{{ $enrollment->student_id
                  ? 'Send '.$enrollment->name.' their login details again? If they haven\'t set their own password yet, a new temporary one is generated and the old one stops working.'
                  : 'Issue a student ID and portal login for '.$enrollment->name.', then send the details by email and SMS?' }}">
            @csrf
            <button type="submit" title="{{ $enrollment->student_id ? 'Resend login details' : 'Issue student ID & login' }}"
                    aria-label="{{ $enrollment->student_id ? 'Resend login details' : 'Issue student ID and login' }}">
                <i class="fa-solid {{ $enrollment->student_id ? 'fa-paper-plane' : 'fa-id-card' }}"></i>
            </button>
        </form>
    @else
        {{-- Nothing to issue yet: the ID goes out when the registration is
             finished, not while it's half-done. --}}
        <span title="Available once the registration is complete" style="color:var(--ink-soft);">
            <i class="fa-solid fa-id-card"></i>
        </span>
    @endif
</div>
