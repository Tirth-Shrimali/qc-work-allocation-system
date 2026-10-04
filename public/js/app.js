(function () {
    'use strict';

    // ---------- Sidebar (mobile drawer) ----------
    var sidebar = document.getElementById('appSidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    var toggle = document.getElementById('sidebarToggle');
    var closeBtn = document.getElementById('sidebarClose');

    function openSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('show');
        if (backdrop) backdrop.classList.add('show');
    }
    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('show');
        if (backdrop) backdrop.classList.remove('show');
    }

    if (toggle) toggle.addEventListener('click', openSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (backdrop) backdrop.addEventListener('click', closeSidebar);

    // ---------- Global search: quick jump to work orders ----------
    var search = document.getElementById('globalSearch');
    if (search) {
        search.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && search.value.trim()) {
                window.location.href = '/work-orders?q=' + encodeURIComponent(search.value.trim());
            }
        });
    }

    // ---------- Loading state on form submit ----------
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (form.tagName !== 'FORM' || form.dataset.noLoader === 'true') return;
        if (!form.checkValidity || !form.checkValidity()) return;

        var btn = form.querySelector('button[type="submit"]:not([data-no-loader])');
        if (btn && !btn.dataset.originalHtml) {
            btn.dataset.originalHtml = btn.innerHTML;
            btn.classList.add('btn-loading');
            btn.setAttribute('disabled', 'disabled');
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Please wait…';
        }
    });

    // ---------- Confirm dialogs ----------
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (form.tagName !== 'FORM') return;
        var confirmMsg = form.dataset.confirm;
        if (confirmMsg && !window.confirm(confirmMsg)) {
            e.preventDefault();
        }
    });

    // ---------- Toast helper ----------
    window.qcToast = function (type, message) {
        var container = document.getElementById('toastContainer');
        if (!container) return;
        var bg = { success: 'text-bg-success', error: 'text-bg-danger', info: 'text-bg-primary' }[type] || 'text-bg-primary';
        var el = document.createElement('div');
        el.className = 'toast align-items-center ' + bg + ' border-0';
        el.setAttribute('role', 'alert');
        el.innerHTML = '<div class="d-flex"><div class="toast-body">' + message +
            '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
        container.appendChild(el);
        var toast = new bootstrap.Toast(el, { delay: 4000 });
        toast.show();
        el.addEventListener('hidden.bs.toast', function () { el.remove(); });
    };

    // ---------- AJAX comment helper ----------
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-ajax-comment]');
        if (!btn) return;
        e.preventDefault();
        var form = btn.closest('form');
        if (!form) return;
        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: new FormData(form)
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.ok) {
                    var list = document.getElementById('commentList');
                    if (list) {
                        var empty = list.querySelector('.empty-comment');
                        if (empty) empty.remove();
                        var li = document.createElement('div');
                        li.className = 'comment-entry';
                        li.innerHTML = '<strong>' + data.comment.user + '</strong>' +
                            '<small class="text-muted ms-2">' + data.comment.created_at + '</small>' +
                            '<p class="mb-0">' + data.comment.comment.replace(/</g, '&lt;') + '</p>';
                        list.prepend(li);
                    }
                    form.reset();
                    window.qcToast('success', 'Comment added.');
                } else if (data.message) {
                    window.qcToast('error', data.message);
                }
            })
            .catch(function () { window.qcToast('error', 'Something went wrong. Please try again.'); });
    });
})();
