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

    {{-- Payment chasers. Each is shown only when that money is actually owed:
         a button you can press on someone who has already paid is a button
         that eventually gets pressed. --}}
    @if ($enrollment->owesFormFee())
        <form method="POST" action="{{ route('dashboard.course-registrations.remind-form', $enrollment) }}"
              data-confirm="Text {{ $enrollment->name }} on {{ $enrollment->phone }} a reminder to pay the {{ $enrollment->amount_label }} form fee? The message carries a link that re-opens their payment.">
            @csrf
            <button type="submit" title="Remind: form fee unpaid" aria-label="Send form-fee reminder by SMS">
                <i class="fa-solid fa-comment-dollar"></i>
            </button>
        </form>
    @elseif ($enrollment->owesTuition())
        <form method="POST" action="{{ route('dashboard.course-registrations.remind-tuition', $enrollment) }}"
              data-confirm="Text {{ $enrollment->name }} on {{ $enrollment->phone }} a reminder to pay their outstanding tuition of {{ App\Models\Course::money($enrollment->tuitionBalance()) }}?">
            @csrf
            <button type="submit" title="Remind: tuition outstanding" aria-label="Send tuition reminder by SMS">
                <i class="fa-solid fa-comment-dollar"></i>
            </button>
        </form>
    @else
        <span title="Nothing outstanding" style="color:var(--ink-soft);">
            <i class="fa-solid fa-comment-dollar"></i>
        </span>
    @endif

    @include('dashboard.partials.row-delete', [
        'id' => $enrollment->id,
        'title' => 'Delete '.$enrollment->name.'?',
        'text' => 'The registration and its payment record go for good. The student account and its student ID are kept.',
    ])
</div>
