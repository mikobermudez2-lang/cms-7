const appRootAttr = document.body && document.body.dataset ? document.body.dataset.appRoot : '';
const APP_ROOT = appRootAttr || '';

function appUrl(path) {
    const sanitized = path.startsWith('/') ? path.slice(1) : path;
    if (!APP_ROOT) {
        return `/${sanitized}`;
    }
    const separator = APP_ROOT.endsWith('/') ? '' : '/';
    return `${APP_ROOT}${separator}${sanitized}`;
}

document.addEventListener('DOMContentLoaded', () => {
    const flash = document.querySelector('[data-flash]');
    if (flash) {
        setTimeout(() => flash.remove(), 4000);
    }
});

