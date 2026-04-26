<x-app-layout>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="payment-wrapper">
                <div class="payment-header">
                    <h2>Complete Your Payment</h2>
                    <p>Provide your details below to securely process your order.</p>
                </div>

                <div class="payment-body">
                    @if ($errors->any())
                        <div class="error-alert">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="#" method="POST">
                        @csrf

                        <div class="form-section">
                            <div class="section-title">
                                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                Personal Details
                            </div>
                            <div class="grid-2">
                                <div class="input-group">
                                    <label>Phone Number</label>
                                    <input type="text" name="phone_number" value="{{ old('phone_number') }}" placeholder="+20 100 000 0000" required>
                                </div>
                                <div class="input-group">
                                    <label>Amount (EGP)</label>
                                    <input type="number" step="0.01" name="amount" value="{{ old('amount') }}" placeholder="0.00" required style="font-size: 1.25rem; font-weight: 600; color: #4f46e5;">
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="section-title">
                                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                Billing Address
                            </div>
                            <div class="grid-3">
                                <div class="input-group full-width">
                                    <label>Street Address</label>
                                    <input type="text" name="street" value="{{ old('street') }}" placeholder="123 Main St">
                                </div>
                                <div class="input-group">
                                    <label>Building</label>
                                    <input type="text" name="building" value="{{ old('building') }}" placeholder="Apt / Suite">
                                </div>
                                <div class="input-group">
                                    <label>Floor</label>
                                    <input type="text" name="floor" value="{{ old('floor') }}" placeholder="e.g. 3">
                                </div>
                                <div class="input-group">
                                    <label>Apartment</label>
                                    <input type="text" name="apartment" value="{{ old('apartment') }}" placeholder="e.g. 32">
                                </div>
                                <div class="input-group">
                                    <label>City</label>
                                    <input type="text" name="city" value="{{ old('city') }}" placeholder="Cairo">
                                </div>
                                <div class="input-group">
                                    <label>State / Province</label>
                                    <input type="text" name="state" value="{{ old('state') }}" placeholder="Cairo Governorate">
                                </div>
                                <div class="input-group">
                                    <label>Postal Code</label>
                                    <input type="text" name="postal_code" value="{{ old('postal_code') }}" placeholder="11511">
                                </div>
                                <div class="input-group full-width">
                                    <label>Country</label>
                                    <select name="country">
                                        <option value="EG" {{ old('country', 'EG') === 'EG' ? 'selected' : '' }}>Egypt (EG)</option>
                                        <option value="US" {{ old('country') === 'US' ? 'selected' : '' }}>United States (US)</option>
                                        <option value="UK" {{ old('country') === 'UK' ? 'selected' : '' }}>United Kingdom (UK)</option>
                                        <option value="SA" {{ old('country') === 'SA' ? 'selected' : '' }}>Saudi Arabia (SA)</option>
                                        <option value="AE" {{ old('country') === 'AE' ? 'selected' : '' }}>United Arab Emirates (AE)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-section" style="background: #ffffff; border-color: #e5e7eb;">
                            <div class="section-title">
                                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                Payment Method
                            </div>
                                <label class="method-card">
                                    <input type="radio" name="payment_method" value="cod" {{ old('payment_method') === 'cod' ? 'checked' : '' }}>
                                    <div class="card-content">
                                        <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path><path d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-15V4a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17H4a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v10a2 2 0 01-2 2h-1"></path></svg>
                                        Cash on Delivery
                                    </div>
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="submit-btn" id="payBtn">
                            Confirm & Pay
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
