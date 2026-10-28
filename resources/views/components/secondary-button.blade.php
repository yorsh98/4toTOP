<button {{ $attributes->merge(['type' => 'button', 'class' => 'bo-btn bo-btn--secondary']) }}>
    {{ $slot }}
</button>
