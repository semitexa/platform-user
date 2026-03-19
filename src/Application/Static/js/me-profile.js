import { renderAvatar, renderCompletenessCircle } from './lib/avatar.js';

let profileData = null;

async function fetchProfile() {
    const res = await fetch('/api/platform/users/me', { credentials: 'include' });
    if (!res.ok) throw new Error('Failed to fetch profile');
    return await res.json();
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
        fieldsSection.innerHTML = '<h3>Your Profile</h3>';

        for (const field of (profileData.fields || [])) {
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

        // Roles (read only)
        if (profileData.roles && profileData.roles.length > 0) {
            const rolesSection = document.createElement('div');
            rolesSection.className = 'form-section';
            rolesSection.innerHTML = '<h3>Your Roles</h3>';
            const rolesDiv = document.createElement('div');
            rolesDiv.className = 'badges';
            for (const role of profileData.roles) {
                const badge = document.createElement('span');
                badge.className = 'badge';
                if (role.slug === 'admin') badge.classList.add('badge-admin');
                badge.textContent = role.name;
                rolesDiv.appendChild(badge);
            }
            rolesSection.appendChild(rolesDiv);
            container.appendChild(rolesSection);
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
    const res = await fetch('/api/platform/users/me/profile', {
        method: 'PATCH',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ fields }),
    });
    if (res.ok) {
        await renderPage();
    } else {
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
