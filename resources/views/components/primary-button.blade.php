<button {{ $attributes->merge(['type' => 'submit', 'class' => 'bo-btn bo-btn--primary']) }}>
    {{ $slot }}
</button>
