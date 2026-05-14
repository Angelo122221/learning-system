const DEFAULT_BASE_PATH = '';

const DOM_URL_ATTRIBUTES = [
    'action',
    'data-lazy-src',
    'data-src',
    'href',
    'poster',
    'src',
];

function normalizeBasePath(basePath) {
    const normalized = (basePath || DEFAULT_BASE_PATH).trim();

    if (!normalized || normalized === '/') {
        return '';
    }

    return `/${normalized.replace(/^\/+|\/+$/g, '')}`;
}

export const appBasePath = normalizeBasePath(
    import.meta.env.VITE_APP_BASE_PATH ||
        (import.meta.env.BASE_URL !== '/' ? import.meta.env.BASE_URL : ''),
);

function hasProtocol(url) {
    return /^[a-z][a-z\d+\-.]*:/i.test(url);
}

function isIgnoredUrl(url) {
    const trimmed = url.trim();

    return (
        trimmed === '' ||
        trimmed.startsWith('#') ||
        /^(blob|data|javascript|mailto|tel|ws|wss):/i.test(trimmed)
    );
}

function isLocalGeneratedUrl(url) {
    return (
        url.origin === window.location.origin ||
        ['localhost', '127.0.0.1', '::1'].includes(url.hostname)
    );
}

function isAlreadyPrefixed(pathname) {
    return (
        appBasePath === '' ||
        pathname === appBasePath ||
        pathname.startsWith(`${appBasePath}/`)
    );
}

function preserveRoutePlaceholders(path) {
    return path.replace(/%7B/gi, '{').replace(/%7D/gi, '}');
}

export function withBasePath(path) {
    if (!path || appBasePath === '') {
        return path;
    }

    if (path.startsWith('//') || hasProtocol(path)) {
        return path;
    }

    if (!path.startsWith('/')) {
        return `${appBasePath}/${path.replace(/^\/+/, '')}`;
    }

    if (isAlreadyPrefixed(path)) {
        return path;
    }

    return `${appBasePath}${path}`;
}

export function asset(path) {
    if (!path || path.startsWith('//') || hasProtocol(path) || isIgnoredUrl(path)) {
        return path;
    }

    return withBasePath(`/${path.replace(/^\/+/, '')}`);
}

export function toApplicationUrl(url) {
    if (typeof window === 'undefined' || !url) {
        return url;
    }

    if (isIgnoredUrl(url)) {
        return url;
    }

    if (!hasProtocol(url)) {
        if (url.startsWith('//')) {
            const parsed = new URL(`${window.location.protocol}${url}`);
            const path = preserveRoutePlaceholders(
                `${parsed.pathname}${parsed.search}${parsed.hash}`,
            );

            return isLocalGeneratedUrl(parsed) ? withBasePath(path) : url;
        }

        return withBasePath(url);
    }

    const parsed = new URL(url);

    if (!isLocalGeneratedUrl(parsed)) {
        return url;
    }

    const path = preserveRoutePlaceholders(
        `${parsed.pathname}${parsed.search}${parsed.hash}`,
    );

    return isAlreadyPrefixed(parsed.pathname) ? path : withBasePath(path);
}

function normalizeSrcset(value) {
    return value
        .split(',')
        .map((candidate) => {
            const parts = candidate.trim().split(/\s+/);
            const url = parts.shift();

            if (!url) {
                return candidate;
            }

            return [toApplicationUrl(url), ...parts].join(' ');
        })
        .join(', ');
}

function normalizeDomAttribute(element, attribute) {
    const currentValue = element.getAttribute(attribute);

    if (!currentValue) {
        return;
    }

    const normalized =
        attribute === 'srcset'
            ? normalizeSrcset(currentValue)
            : toApplicationUrl(currentValue);

    if (normalized !== currentValue) {
        element.setAttribute(attribute, normalized);
    }
}

function normalizeDomElement(element) {
    DOM_URL_ATTRIBUTES.forEach((attribute) => {
        normalizeDomAttribute(element, attribute);
    });

    normalizeDomAttribute(element, 'srcset');
}

function normalizeDomTree(root) {
    if (root instanceof Element) {
        normalizeDomElement(root);
    }

    root.querySelectorAll?.(
        DOM_URL_ATTRIBUTES.map((attribute) => `[${attribute}]`)
            .concat('[srcset]')
            .join(','),
    ).forEach(normalizeDomElement);
}

function configureSetAttributeBasePath() {
    const originalSetAttribute = Element.prototype.setAttribute;

    Element.prototype.setAttribute = function (qualifiedName, value) {
        const normalizedName = qualifiedName.toLowerCase();
        const normalizedValue =
            normalizedName === 'srcset'
                ? normalizeSrcset(String(value))
                : DOM_URL_ATTRIBUTES.includes(normalizedName)
                  ? toApplicationUrl(String(value))
                  : value;

        originalSetAttribute.call(this, qualifiedName, normalizedValue);
    };
}

export function configureDomBasePath() {
    if (
        typeof window === 'undefined' ||
        typeof document === 'undefined' ||
        appBasePath === '' ||
        window.__crystalBasePathDomConfigured
    ) {
        return;
    }

    window.__crystalBasePathDomConfigured = true;
    configureSetAttributeBasePath();
    normalizeDomTree(document);

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'attributes') {
                normalizeDomElement(mutation.target);

                return;
            }

            mutation.addedNodes.forEach((node) => {
                if (node instanceof Element) {
                    normalizeDomTree(node);
                }
            });
        });
    });

    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: [...DOM_URL_ATTRIBUTES, 'srcset'],
        childList: true,
        subtree: true,
    });
}

function resolveFetchInput(input) {
    if (typeof input === 'string') {
        return toApplicationUrl(input);
    }

    if (input instanceof URL) {
        return new URL(toApplicationUrl(input.toString()));
    }

    if (input instanceof Request) {
        return new Request(toApplicationUrl(input.url), input);
    }

    return input;
}

export function configureFetchBasePath() {
    if (typeof window === 'undefined' || window.__crystalFetchBasePathConfigured) {
        return;
    }

    window.__crystalFetchBasePathConfigured = true;
    const originalFetch = window.fetch.bind(window);

    window.fetch = (input, init) =>
        originalFetch(resolveFetchInput(input), init);
}

export function configureAxiosBasePath(axiosInstance) {
    const instance =
        axiosInstance ||
        (typeof window !== 'undefined' ? window.axios : undefined);

    if (!instance) {
        return;
    }

    instance.defaults.baseURL = '/';
    instance.defaults.withCredentials = true;

    instance.interceptors?.request?.use((config) => {
        if (config.url) {
            config.url = toApplicationUrl(config.url);
        }

        return config;
    });
}

export function configureInertiaRouterBasePath(router) {
    if (!router || router.__crystalBasePathConfigured) {
        return;
    }

    router.__crystalBasePathConfigured = true;

    ['visit', 'get', 'post', 'put', 'patch', 'delete'].forEach((method) => {
        const originalMethod = router[method];

        if (typeof originalMethod !== 'function') {
            return;
        }

        router[method] = function (url, ...args) {
            return originalMethod.call(this, toApplicationUrl(url), ...args);
        };
    });
}

function configureWayfinderRoute(value) {
    if (!value || typeof value !== 'function') {
        return;
    }

    if (typeof value.definition?.url === 'string') {
        value.definition.url = toApplicationUrl(value.definition.url);
    }
}

function configureWayfinderValue(value) {
    configureWayfinderRoute(value);

    if (!value || typeof value !== 'object') {
        return;
    }

    Object.values(value).forEach(configureWayfinderValue);
}

export function configureWayfinderBasePath(modules) {
    modules.forEach((module) => {
        Object.values(module).forEach(configureWayfinderValue);
    });
}

export function configureZiggyBasePath() {
    if (
        typeof window === 'undefined' ||
        !window.Ziggy ||
        appBasePath === '' ||
        window.__crystalZiggyBasePathConfigured
    ) {
        return;
    }

    window.__crystalZiggyBasePathConfigured = true;
    window.Ziggy.url = `${window.location.origin}${appBasePath}`;
    window.Ziggy.location = new URL(window.location.href);
}
