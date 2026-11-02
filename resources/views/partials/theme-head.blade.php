{{-- Fija el tema antes del primer pintado: elección guardada, si no la del sistema, si no claro.
     No escribe fo-theme. La misma regla vive en resources/js/theme.js. --}}
<script>
    (function () {
        var theme = null;
        try { theme = localStorage.getItem('fo-theme'); } catch (e) {}
        if (theme !== 'light' && theme !== 'dark') {
            try { theme = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; } catch (e) { theme = 'light'; }
        }
        document.documentElement.dataset.theme = theme;
    })();
</script>
