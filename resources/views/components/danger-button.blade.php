<button {{ $attributes->merge(['type' => 'submit', 'class' => 'bo-btn bo-btn--danger']) }}>
    {{ $slot }}
</button>
