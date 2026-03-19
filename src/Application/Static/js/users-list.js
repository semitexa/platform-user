import { renderAvatar, renderCompletenessBar } from './lib/avatar.js';

let debounceTimer = null;
let currentData = [];
let wm = null;

async function fetchUsers(search = '') {
    const params = new URLSearchParams({ limit: '50' });
    if (search) params.set('search', search);

    const res = await fetch('/api/platform/users?' + params, { credentials: 'include' });
    if (!res.ok) throw new Error('Failed to fetch users');
    const data = await res.json();
    return data.users || [];
}

function renderBadge(role) {
    const span = document.createElement('span');
    span.className = 'badge';
    if (role.slug === 'admin') span.classList.add('badge-admin');
    else if (role.slug === 'moderator') span.classList.add('badge-moderator');
    span.textContent = role.name || role.slug;
    return span;
}

function renderTable(users) {
    const tbody = document.getElementById('users-tbody');
    tbody.innerHTML = '';

    if (users.length === 0) {
        const tr = document.createElement('tr');
        tr.innerHTML = '<td colspan="6"><div class="empty-state"><div class="empty-state-icon">👥</div>No users found</div></td>';
        tbody.appendChild(tr);
        return;
    }

    for (const user of users) {
        const tr = document.createElement('tr');
        tr.onclick = () => {
            if (wm) {
                wm.open('user-profile', { id: user.id });
            } else {
                window.location = '/platform/users/' + user.id;
            }
        };

        // User cell
        const tdUser = document.createElement('td');
        const userCell = document.createElement('div');
        userCell.className = 'user-cell';
        userCell.appendChild(renderAvatar(user.name, user.avatar_url));
        const info = document.createElement('div');
        info.className = 'user-cell-info';
        info.innerHTML = `<span class="user-cell-name">${esc(user.name)}</span><span class="user-cell-email">${esc(user.email)}</span>`;
        userCell.appendChild(info);
        tdUser.appendChild(userCell);
        tr.appendChild(tdUser);

        // Roles
        const tdRoles = document.createElement('td');
        const badges = document.createElement('div');
        badges.className = 'badges';
        if (user.roles && user.roles.length > 0) {
            for (const role of user.roles) {
                badges.appendChild(renderBadge(role));
            }
        }
        tdRoles.appendChild(badges);
        tr.appendChild(tdRoles);

        // Status
        const tdStatus = document.createElement('td');
        tdStatus.innerHTML = `<span class="status-dot ${user.is_active ? 'active' : 'inactive'}"></span>${user.is_active ? 'Active' : 'Inactive'}`;
        tr.appendChild(tdStatus);

        // Last login
        const tdLogin = document.createElement('td');
        tdLogin.textContent = user.last_login ? new Date(user.last_login).toLocaleDateString() : 'Never';
        tdLogin.style.color = '#a6adc8';
        tdLogin.style.fontSize = '13px';
        tr.appendChild(tdLogin);

        // Completeness
        const tdComp = document.createElement('td');
        tdComp.appendChild(renderCompletenessBar(user.profile_completeness || 0));
        tr.appendChild(tdComp);

        tbody.appendChild(tr);
    }
}

function esc(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}

async function loadUsers(search = '') {
    const loading = document.getElementById('loading');
    loading.style.display = 'block';
    try {
        currentData = await fetchUsers(search);
        renderTable(currentData);
    } catch (e) {
        console.error(e);
    } finally {
        loading.style.display = 'none';
    }
}

function initSearch() {
    const input = document.getElementById('search-input');
    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            loadUsers(input.value.trim());
        }, 300);
    });
}

function initAddUser() {
    const btn = document.getElementById('add-user-btn');

    btn.addEventListener('click', () => {
        if (wm) {
            wm.open('user-create');
        }
    });
}

function initWmEvents() {
    if (!wm) return;

    // Refresh user list when a child window closes (e.g. after creating a user)
    wm.on('window.close', () => {
        loadUsers();
    });
}

// Init
document.addEventListener('DOMContentLoaded', () => {
    if (typeof SemitexaWM !== 'undefined') {
        wm = SemitexaWM.init();
    }
    initSearch();
    initAddUser();
    initWmEvents();
    loadUsers();
});
