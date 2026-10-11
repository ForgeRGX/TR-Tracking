
(() => {
    const status = document.getElementById('version-status');
    const checked = document.getElementById('version-checked');
    const releaseLink = document.getElementById('version-release-link');

    if (!status || !checked || !releaseLink) return;

    const interval = 5 * 60 * 1000;

    function showStatus(text, style) {
        status.textContent = text;
        status.className = `badge ${style} ms-1`;
    }

    function formatDate(value) {
        if (!value) return '';

        const date = new Date(value);

        return Number.isNaN(date.getTime())
            ? ''
            : `Verified: ${date.toLocaleString()}`;
    }

    async function checkVersion() {
        try {
            const response = await fetch('/api/version', {
                method: 'GET',
                cache: 'no-store',
                headers: { Accept: 'application/json' }
            });

            if (!response.ok) {
                throw new Error('The version could not be retrieved.');
            }

            const data = await response.json();

            if (data.status !== 'ok') {
                showStatus(
                    'It could not be verified',
                    'text-bg-secondary'
                );
                checked.textContent = formatDate(data.checkedAt);
                releaseLink.hidden = true;
                return;
            }

            checked.textContent = formatDate(data.checkedAt);

            if (data.releaseUrl) {
                releaseLink.href = data.releaseUrl;
                releaseLink.hidden = false;
            } else {
                releaseLink.hidden = true;
            }

            if (data.updateAvailable === true) {
                showStatus(
                    `New version: ${data.latest}`,
                    'text-bg-warning'
                );
            } else if (data.upToDate === true) {
                showStatus(
                    'Updated',
                    'text-bg-success'
                );
                releaseLink.hidden = true;
            } else {
                showStatus(
                    `Dev · Stable: ${data.latest}`,
                    'text-bg-secondary'
                );
            }
        } catch {
            showStatus(
                'It could not be verified',
                'text-bg-secondary'
            );
            checked.textContent = '';
            releaseLink.hidden = true;
        } finally {
            window.setTimeout(checkVersion, interval);
        }
    }

    checkVersion();
})();