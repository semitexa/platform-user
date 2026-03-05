/**
 * Shared avatar rendering with initials fallback and deterministic color.
 */

const AVATAR_COLORS = [
    '#89b4fa', '#a6e3a1', '#f38ba8', '#f9e2af', '#fab387',
    '#cba6f7', '#94e2d5', '#b4befe', '#89dceb', '#f5c2e7',
];

function hashString(str) {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        hash = ((hash << 5) - hash) + str.charCodeAt(i);
        hash |= 0;
    }
    return Math.abs(hash);
}

export function getInitials(name) {
    if (!name) return '?';
    const parts = name.trim().split(/\s+/);
    if (parts.length === 1) return parts[0][0].toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

export function getAvatarColor(name) {
    return AVATAR_COLORS[hashString(name || '') % AVATAR_COLORS.length];
}

export function renderAvatar(name, avatarUrl, className = 'avatar') {
    const el = document.createElement('div');
    el.className = className;

    if (avatarUrl) {
        const img = document.createElement('img');
        img.src = avatarUrl;
        img.alt = name || '';
        img.onerror = () => {
            img.remove();
            el.textContent = getInitials(name);
            el.style.background = getAvatarColor(name);
        };
        el.appendChild(img);
    } else {
        el.textContent = getInitials(name);
        el.style.background = getAvatarColor(name);
    }

    return el;
}

export function renderCompletenessBar(percent) {
    const bar = document.createElement('div');
    bar.className = 'completeness-bar';

    const fill = document.createElement('div');
    fill.className = 'completeness-fill';
    if (percent < 40) fill.classList.add('low');
    else if (percent < 80) fill.classList.add('mid');
    else fill.classList.add('high');
    fill.style.width = percent + '%';

    bar.appendChild(fill);

    const text = document.createElement('div');
    text.className = 'completeness-text';
    text.textContent = Math.round(percent) + '%';

    const wrapper = document.createElement('div');
    wrapper.appendChild(bar);
    wrapper.appendChild(text);
    return wrapper;
}

export function renderCompletenessCircle(percent) {
    const size = 60;
    const stroke = 4;
    const radius = (size - stroke) / 2;
    const circumference = 2 * Math.PI * radius;
    const offset = circumference - (percent / 100) * circumference;

    let color = '#f38ba8';
    if (percent >= 80) color = '#a6e3a1';
    else if (percent >= 40) color = '#f9e2af';

    const wrapper = document.createElement('div');
    wrapper.className = 'completeness-circle';
    wrapper.innerHTML = `
        <svg width="${size}" height="${size}">
            <circle cx="${size/2}" cy="${size/2}" r="${radius}" fill="none" stroke="#313244" stroke-width="${stroke}"/>
            <circle cx="${size/2}" cy="${size/2}" r="${radius}" fill="none" stroke="${color}" stroke-width="${stroke}"
                stroke-dasharray="${circumference}" stroke-dashoffset="${offset}" stroke-linecap="round"/>
        </svg>
        <div class="completeness-circle-text">${Math.round(percent)}%</div>
    `;
    return wrapper;
}
