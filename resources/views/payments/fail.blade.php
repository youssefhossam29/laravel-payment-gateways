<x-guest-layout>
    <div class="fail-page">
        <div class="fail-card">
            <div class="fail-icon-wrap">
                <svg fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </div>
            <h2 class="fail-title">Payment Failed</h2>
            <p class="fail-message">
                {{ __("We couldn't process your payment. Please check your payment details and ensure you have sufficient funds, or try a different payment method.") }}
            </p>
            <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                <a href="{{ route('payment.create') ?? url()->previous() }}" class="submit-btn">Try Again</a>
            </div>
        </div>
    </div>
</x-guest-layout>
