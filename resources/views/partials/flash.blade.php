@if (session('success'))
    <div class="d-none" data-flash='{"type":"success","message":"{{ addslashes(session('success')) }}"}'></div>
@endif
@if ($errors->any())
    <div class="d-none" data-flash='{"type":"error","message":"{{ addslashes($errors->first()) }}"}'></div>
@endif
@if (session('error'))
    <div class="d-none" data-flash='{"type":"error","message":"{{ addslashes(session('error')) }}"}'></div>
@endif

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-flash]').forEach(function (el) {
                    try {
                        var data = JSON.parse(el.getAttribute('data-flash'));
                        window.qcToast(data.type, data.message);
                    } catch (e) {}
                });
            });
        </script>
    @endpush
@endonce
