/**
 * Lumitask API client — AJAX without full page reload
 */
const LumitaskApi = (() => {
    const base = (window.LUMITASK_BASE || '/Lumitask') + '/api.php?route=';

    async function request(route, options = {}) {
        const method = (options.method || 'GET').toUpperCase();
        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
            ...(options.headers || {}),
        };

        const csrf = window.DASHBOARD_DATA?.csrf || '';
        if (csrf) {
            headers['X-CSRF-TOKEN'] = csrf;
        }

        let url = base + encodeURI(route);
        const init = { method, headers, credentials: 'same-origin' };

        if (options.body instanceof FormData) {
            if (csrf) options.body.append('_csrf', csrf);
            init.body = options.body;
        } else if (options.json) {
            headers['Content-Type'] = 'application/json';
            init.body = JSON.stringify({ ...options.json, _csrf: csrf });
        } else if (method !== 'GET') {
            const fd = new FormData();
            Object.entries(options.data || {}).forEach(([k, v]) => fd.append(k, v));
            if (csrf) fd.append('_csrf', csrf);
            init.body = fd;
        } else if (options.params) {
            const qs = new URLSearchParams(options.params).toString();
            url += (url.includes('?') ? '&' : '?') + qs;
        }

        const res = await fetch(url, init);
        const data = await res.json().catch(() => ({}));

        if (!res.ok || data.ok === false) {
            const err = new Error(data.error || 'Request failed');
            err.payload = data;
            throw err;
        }

        return data;
    }

    return {
        getTasks: (params) => request('/api/tasks', { params }),
        createTask: (data) => request('/api/tasks', { method: 'POST', data }),
        completeTask: (id) => request('/api/tasks/complete', { method: 'POST', data: { id } }),
        deleteTask: (id) => request('/api/tasks/delete', { method: 'POST', data: { id } }),
        getStats: () => request('/api/dashboard/stats'),
        getNotifications: () => request('/api/notifications'),
        markNotificationsRead: (id) => request('/api/notifications/read', { method: 'POST', data: id ? { id } : {} }),
        getActivity: () => request('/api/activity'),
        pollCollaboration: (since) => request('/api/collaboration/poll', { params: { since } }),
        createList: (data) => request('/api/lists', { method: 'POST', data }),
        updateList: (data) => request('/api/lists/update', { method: 'POST', data }),
        deleteList: (data) => request('/api/lists/delete', { method: 'POST', data }),
        uploadAttachment: (taskId, file) => {
            const fd = new FormData();
            fd.append('task_id', taskId);
            fd.append('file', file);
            return request('/api/attachments', { method: 'POST', body: fd });
        },
        sharePublicTask: (data) => request('/api/public/tasks', { method: 'POST', data }),
        joinPublicTask: (token) => request('/api/public/tasks/join', { method: 'POST', data: { token } }),
    };
})();
