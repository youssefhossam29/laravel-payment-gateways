<x-guest-layout>
    <div class="success-page">
        <div class="success-card">
            <div class="success-icon-wrap">
                <svg fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            <h2 class="success-title">Payment Successful!</h2>
            <p class="success-message">
                {{ __("Thank you! Your payment has been processed successfully. A confirmation email has been sent to your registered address.") }}
            </p>
            <a href="{{ url('/') }}" class="action-button">Return to Dashboard</a>
        </div>
    </div>
</x-guest-layout>
