const wm = typeof SemitexaWM !== 'undefined' ? SemitexaWM.init() : null;

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('create-user-form');
    const cancelBtn = document.getElementById('cancel-btn');
    const errorMsg = document.getElementById('error-msg');

    cancelBtn.addEventListener('click', () => {
        if (wm) {
            wm.closeSelf();
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        errorMsg.style.display = 'none';

        const data = {
            email: form.email.value,
            name: form.uname.value,
            password: form.password.value,
        };

        try {
            const res = await fetch('/api/platform/users', {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
            });

            if (res.ok) {
                if (wm) {
                    wm.closeSelf();
                }
            } else {
                const err = await res.json();
                errorMsg.textContent = err.error || 'Failed to create user';
                errorMsg.style.display = 'block';
            }
        } catch (err) {
            errorMsg.textContent = 'Connection error';
            errorMsg.style.display = 'block';
        } finally {
            submitBtn.disabled = false;
        }
    });
});
