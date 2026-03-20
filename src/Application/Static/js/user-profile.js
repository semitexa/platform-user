import { renderAvatar, renderCompletenessCircle } from './lib/avatar.js';

const userId = window.__USER_ID__;
let profileFields = [];
let profileData = null;

async function fetchProfile() {
    const res = await fetch(`/api/platform/users/${userId}/profile`, { credentials: 'include' });
    if (!res.ok) throw new Error('Failed to fetch profile');
    return await res.json();
}

async function fetchActivity() {
    const res = await fetch(`/api/platform/users/${userId}/activity`, { credentials: 'include' });
    if (!res.ok) return [];
    const data = await res.json();
    return data.activity || [];
}

async function fetchAllRoles() {
    const res = await fetch('/api/platform/roles', { credentials: 'include' });
    if (!res.ok) return [];
    const data = await res.json();
    return data.roles || [];
}

function renderFieldInput(field, value) {
    const wrapper = document.createElement('div');
    wrapper.className = 'form-field';

    const label = document.createElement('label');
    label.textContent = (field.icon ? field.icon + ' ' : '') + field.label;
    if (field.is_required) label.textContent += ' *';
    wrapper.appendChild(label);

    let input;
    switch (field.type) {
        case 'textarea':
            input = document.createElement('textarea');
            input.value = value || '';
            break;
        case 'select':
            input = document.createElement('select');
            const emptyOpt = document.createElement('option');
            emptyOpt.value = '';
            emptyOpt.textContent = '— Select —';
            input.appendChild(emptyOpt);
            for (const opt of (field.options || [])) {
                const o = document.createElement('option');
                o.value = typeof opt === 'object' ? opt.value : opt;
                o.textContent = typeof opt === 'object' ? opt.label : opt;
                if (o.value === (value || '')) o.selected = true;
                input.appendChild(o);
            }
            break;
        case 'file':
            input = document.createElement('div');
            input.className = 'file-input-wrapper';
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.dataset.slug = field.slug;
            fileInput.className = 'file-field-input';
            const preview = document.createElement('div');
            preview.className = 'file-preview';
            preview.textContent = value ? 'File attached' : 'Click to upload';
            input.appendChild(fileInput);
            input.appendChild(preview);
            break;
        case 'url':
            input = document.createElement('input');
            input.type = 'url';
            input.value = value || '';
            break;
        case 'date':
            input = document.createElement('input');
            input.type = 'date';
            input.value = value || '';
            break;
        default:
            input = document.createElement('input');
            input.type = 'text';
            input.value = value || '';
    }

    if (input.tagName) {
        input.dataset.slug = field.slug;
        input.dataset.type = field.type;
    }

    wrapper.appendChild(input);
    return wrapper;
}

async function uploadFile(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = async () => {
            const base64 = reader.result.split(',')[1];
            try {
                const res = await fetch('/api/platform/files', {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name: file.name,
                        mime_type: file.type,
                        contents: base64,
                    }),
                });
                if (!res.ok) throw new Error('Upload failed');
                const data = await res.json();
                resolve(data.file.id);
            } catch (e) {
                reject(e);
            }
        };
        reader.readAsDataURL(file);
    });
}

async function renderPage() {
    const container = document.getElementById('profile-container');
    container.innerHTML = '<div class="spinner"></div>';

    try {
        profileData = await fetchProfile();
        const [activity, allRoles] = await Promise.all([fetchActivity(), fetchAllRoles()]);

        container.innerHTML = '';

        // Header
        const header = document.createElement('div');
        header.className = 'profile-header';

        const avatarField = (profileData.fields || []).find(f => f.slug === 'avatar');
        const avatarUrl = avatarField?.file_id ? `/api/platform/files/${avatarField.file_id}` : null;

        const avatarEl = renderAvatar(profileData.user.name, avatarUrl, 'avatar avatar-lg');
        const hiddenFile = document.createElement('input');
        hiddenFile.type = 'file';
        hiddenFile.accept = 'image/*';
        hiddenFile.style.display = 'none';
        hiddenFile.id = 'avatar-upload';
        avatarEl.onclick = () => hiddenFile.click();
        hiddenFile.onchange = async () => {
            if (!hiddenFile.files[0]) return;
            try {
                const fileId = await uploadFile(hiddenFile.files[0]);
                await saveProfile({ avatar: 'file:' + fileId });
                await renderPage();
            } catch (e) {
                alert('Upload failed');
            }
        };
        header.appendChild(avatarEl);
        header.appendChild(hiddenFile);

        const info = document.createElement('div');
        info.className = 'profile-info';
        info.innerHTML = `<h2>${esc(profileData.user.name)}</h2><p>${esc(profileData.user.email)}</p>`;
        header.appendChild(info);

        header.appendChild(renderCompletenessCircle(profileData.profile_completeness || 0));
        container.appendChild(header);

        // Profile fields
        const fieldsSection = document.createElement('div');
        fieldsSection.className = 'form-section';
        fieldsSection.innerHTML = '<h3>Profile Information</h3>';

        const fieldsMap = {};
        for (const f of (profileData.fields || [])) {
            fieldsMap[f.slug] = f;
        }

        const fieldDefs = profileData.fields || [];
        for (const field of fieldDefs) {
            if (field.slug === 'avatar') continue;
            fieldsSection.appendChild(renderFieldInput(field, field.value));
        }

        const saveBtn = document.createElement('button');
        saveBtn.className = 'btn btn-primary';
        saveBtn.textContent = 'Save Profile';
        saveBtn.onclick = () => saveFromForm(fieldsSection);

        const actions = document.createElement('div');
        actions.className = 'form-actions';
        actions.appendChild(saveBtn);
        fieldsSection.appendChild(actions);
        container.appendChild(fieldsSection);

        // Roles section
        const rolesSection = document.createElement('div');
        rolesSection.className = 'form-section';
        rolesSection.innerHTML = '<h3>Roles</h3>';
        const rolesDiv = document.createElement('div');
        rolesDiv.className = 'badges';
        rolesDiv.style.marginBottom = '12px';
        for (const role of (profileData.roles || [])) {
            const badge = document.createElement('span');
            badge.className = 'badge';
            if (role.slug === 'admin') badge.classList.add('badge-admin');
            badge.textContent = role.name;
            rolesDiv.appendChild(badge);
        }
        rolesSection.appendChild(rolesDiv);

        // Role management
        if (allRoles.length > 0) {
            const roleSelect = document.createElement('select');
            roleSelect.id = 'role-select';
            for (const r of allRoles) {
                const opt = document.createElement('option');
                opt.value = r.id;
                opt.textContent = r.name;
                roleSelect.appendChild(opt);
            }
            const addRoleBtn = document.createElement('button');
            addRoleBtn.className = 'btn btn-ghost';
            addRoleBtn.textContent = 'Assign Role';
            addRoleBtn.onclick = async () => {
                const roleIds = (profileData.roles || []).map(r => r.id);
                roleIds.push(roleSelect.value);
                await fetch(`/api/platform/users/${userId}/roles`, {
                    method: 'PUT',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ role_ids: [...new Set(roleIds)] }),
                });
                await renderPage();
            };
            const roleRow = document.createElement('div');
            roleRow.style.display = 'flex';
            roleRow.style.gap = '8px';
            roleRow.appendChild(roleSelect);
            roleRow.appendChild(addRoleBtn);
            rolesSection.appendChild(roleRow);
        }
        container.appendChild(rolesSection);

        // Activity log
        const actSection = document.createElement('div');
        actSection.className = 'form-section';
        actSection.innerHTML = '<h3>Activity Log</h3>';
        const actList = document.createElement('ul');
        actList.className = 'activity-list';
        if (activity.length === 0) {
            actList.innerHTML = '<li class="activity-item"><span class="activity-meta">No activity recorded</span></li>';
        } else {
            for (const a of activity.slice(0, 20)) {
                const li = document.createElement('li');
                li.className = 'activity-item';
                li.innerHTML = `
                    <span class="activity-action">${esc(a.action)}</span>
                    <span class="activity-meta">${a.created_at ? new Date(a.created_at).toLocaleString() : ''}</span>
                    ${a.ip_address ? `<span class="activity-meta">${esc(a.ip_address)}</span>` : ''}
                `;
                actList.appendChild(li);
            }
        }
        actSection.appendChild(actList);
        container.appendChild(actSection);

        // Set WM title
        if (typeof SemitexaWM !== 'undefined' && SemitexaWM.setTitle) {
            SemitexaWM.setTitle('User: ' + profileData.user.name);
        }

    } catch (e) {
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">⚠️</div>Failed to load profile</div>';
        console.error(e);
    }
}

async function saveFromForm(section) {
    const fields = {};
    const inputs = section.querySelectorAll('input[data-slug], textarea[data-slug], select[data-slug]');
    for (const input of inputs) {
        if (input.dataset.type === 'file') continue;
        fields[input.dataset.slug] = input.value;
    }

    // Handle file fields
    const fileInputs = section.querySelectorAll('.file-field-input');
    for (const fi of fileInputs) {
        if (fi.files && fi.files[0]) {
            const fileId = await uploadFile(fi.files[0]);
            fields[fi.dataset.slug] = 'file:' + fileId;
        }
    }

    await saveProfile(fields);
}

async function saveProfile(fields) {
    const res = await fetch(`/api/platform/users/${userId}/profile`, {
        method: 'PATCH',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ fields }),
    });
    if (!res.ok) {
        const err = await res.json();
        alert(err.error || 'Failed to save');
    }
}

function esc(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}

document.addEventListener('DOMContentLoaded', () => {
    if (typeof SemitexaWM !== 'undefined') SemitexaWM.init();
    renderPage();
});
