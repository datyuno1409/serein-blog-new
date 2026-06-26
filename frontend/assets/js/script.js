const SEREIN_LANG_KEY = 'serein_lang';
const SEREIN_I18N_PATH = '/assets/i18n';
const SEREIN_TRANSLATION_CACHE = {};

function getCurrentLanguage() {
    return document.documentElement.getAttribute('data-lang') === 'en' ? 'en' : 'vi';
}

function getStoredLanguage() {
    return localStorage.getItem(SEREIN_LANG_KEY) === 'en' ? 'en' : 'vi';
}

async function loadTranslations(language) {
    const resolved = language === 'en' ? 'en' : 'vi';
    if (SEREIN_TRANSLATION_CACHE[resolved]) {
        return SEREIN_TRANSLATION_CACHE[resolved];
    }

    try {
        const response = await fetch(`${SEREIN_I18N_PATH}/${resolved}.json`, { cache: 'no-cache' });
        if (!response.ok) {
            throw new Error(`Failed to load ${resolved} translations`);
        }
        const translations = await response.json();
        SEREIN_TRANSLATION_CACHE[resolved] = translations;
        return translations;
    } catch (error) {
        console.error(error);
        SEREIN_TRANSLATION_CACHE[resolved] = {};
        return SEREIN_TRANSLATION_CACHE[resolved];
    }
}

function translate(key, translations = SEREIN_TRANSLATION_CACHE[getCurrentLanguage()] || {}) {
    return translations[key] || key;
}

function setI18nText(element, key, translations) {
    const value = translate(key, translations);
    if (value !== key) {
        element.textContent = value;
    }
}

function applyLegacyLanguageVisibility(language) {
    document.querySelectorAll('[data-lang-vi], [data-lang-en]').forEach((element) => {
        const shouldShow = language === 'vi'
            ? element.hasAttribute('data-lang-vi')
            : element.hasAttribute('data-lang-en');
        element.hidden = !shouldShow;
        if (shouldShow) {
            if (element.classList.contains('lang-flex')) {
                element.style.setProperty('display', 'flex', 'important');
            } else if (element.classList.contains('lang-inline-flex')) {
                element.style.setProperty('display', 'inline-flex', 'important');
            } else if (element.classList.contains('lang-block')) {
                element.style.setProperty('display', 'block', 'important');
            } else {
                element.style.setProperty('display', 'inline', 'important');
            }
        } else {
            element.style.setProperty('display', 'none', 'important');
        }
    });
}

function applyTranslatedAttributes(translations) {
    document.querySelectorAll('[data-i18n]').forEach((element) => {
        setI18nText(element, element.dataset.i18n, translations);
    });

    document.querySelectorAll('[data-i18n-placeholder]').forEach((element) => {
        const value = translate(element.dataset.i18nPlaceholder, translations);
        if (value !== element.dataset.i18nPlaceholder) {
            element.setAttribute('placeholder', value);
        }
    });

    document.querySelectorAll('[data-i18n-title]').forEach((element) => {
        const value = translate(element.dataset.i18nTitle, translations);
        if (value !== element.dataset.i18nTitle) {
            element.setAttribute('title', value);
        }
    });

    document.querySelectorAll('[data-i18n-aria-label]').forEach((element) => {
        const value = translate(element.dataset.i18nAriaLabel, translations);
        if (value !== element.dataset.i18nAriaLabel) {
            element.setAttribute('aria-label', value);
        }
    });

    document.querySelectorAll('[data-i18n-placeholder-en], [data-i18n-placeholder-vi]').forEach((element) => {
        const lang = getCurrentLanguage();
        const key = lang === 'vi' ? 'i18nPlaceholderVi' : 'i18nPlaceholderEn';
        if (element.dataset[key]) {
            element.setAttribute('placeholder', element.dataset[key]);
        }
    });

    document.querySelectorAll('[data-i18n-title-en], [data-i18n-title-vi]').forEach((element) => {
        const lang = getCurrentLanguage();
        const key = lang === 'vi' ? 'i18nTitleVi' : 'i18nTitleEn';
        if (element.dataset[key]) {
            element.setAttribute('title', element.dataset[key]);
        }
    });
}

async function setLanguage(language) {
    const resolved = language === 'en' ? 'en' : 'vi';
    document.documentElement.setAttribute('data-lang', resolved);
    document.documentElement.setAttribute('lang', resolved);
    localStorage.setItem(SEREIN_LANG_KEY, resolved);
    applyLegacyLanguageVisibility(resolved);

    const translations = await loadTranslations(resolved);
    applyTranslatedAttributes(translations);
    applyLegacyLanguageVisibility(resolved);

    document.querySelectorAll('[data-lang-btn]').forEach((button) => {
        const isActive = button.dataset.langBtn === resolved;
        button.classList.toggle('active', isActive);
        button.setAttribute('aria-pressed', String(isActive));
    });

    document.dispatchEvent(new CustomEvent('serein:languagechange', {
        detail: { language: resolved, translations },
    }));
}

function getLangText(element) {
    const lang = getCurrentLanguage();
    const keyedText = element.dataset.i18n
        ? translate(element.dataset.i18n)
        : '';
    if (keyedText && keyedText !== element.dataset.i18n) {
        return keyedText.trim();
    }

    const langSpan = element.querySelector(`[data-lang-${lang}]`);
    if (langSpan) {
        return langSpan.textContent.trim();
    }
    return element.textContent.trim();
}

function closeMobileDrawer(mobileDrawer, mobileMenuToggle) {
    mobileDrawer.classList.remove('active');
    mobileMenuToggle.classList.remove('active');
    mobileMenuToggle.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = 'auto';
}

function initNavigation() {
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileDrawer = document.getElementById('mobileDrawer');

    if (mobileMenuToggle && mobileDrawer) {
        mobileMenuToggle.addEventListener('click', () => {
            const shouldOpen = !mobileDrawer.classList.contains('active');
            mobileDrawer.classList.toggle('active', shouldOpen);
            mobileMenuToggle.classList.toggle('active', shouldOpen);
            mobileMenuToggle.setAttribute('aria-expanded', String(shouldOpen));
            document.body.style.overflow = shouldOpen ? 'hidden' : 'auto';
        });

        mobileDrawer.querySelectorAll('.nav-link').forEach((link) => {
            link.addEventListener('click', () => closeMobileDrawer(mobileDrawer, mobileMenuToggle));
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeMobileDrawer(mobileDrawer, mobileMenuToggle);
            }
        });
    }

    const userDropdown = document.getElementById('userDropdown');
    const userDropdownBtn = document.getElementById('userDropdownBtn');
    if (userDropdown && userDropdownBtn) {
        const setUserDropdownOpen = (isOpen) => {
            userDropdown.classList.toggle('active', isOpen);
            userDropdownBtn.setAttribute('aria-expanded', String(isOpen));
        };

        userDropdownBtn.addEventListener('click', (event) => {
            event.stopPropagation();
            setUserDropdownOpen(!userDropdown.classList.contains('active'));
        });

        userDropdown.querySelectorAll('.user-dropdown-item').forEach((item) => {
            item.addEventListener('click', () => setUserDropdownOpen(false));
        });

        document.addEventListener('click', (event) => {
            if (!userDropdown.contains(event.target)) {
                setUserDropdownOpen(false);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                setUserDropdownOpen(false);
                userDropdownBtn.focus();
            }
        });
    }
}

function initLanguageSwitcher() {
    document.querySelectorAll('[data-lang-btn]').forEach((button) => {
        button.addEventListener('click', () => setLanguage(button.dataset.langBtn));
    });
    setLanguage(getStoredLanguage());
}

function initAnimations() {
    document.querySelectorAll('.typewriter').forEach((element, index) => {
        const lang = getCurrentLanguage();
        const langSpan = element.querySelector(`[data-lang-${lang}]`);
        const targetNode = langSpan || element;
        const text = targetNode.textContent.trim();

        if (!text) {
            return;
        }

        targetNode.textContent = '';
        element.style.whiteSpace = 'pre-wrap';
        element.style.wordBreak = 'break-word';

        let pointer = 0;
        const type = () => {
            if (pointer < text.length) {
                targetNode.textContent += text.charAt(pointer);
                pointer += 1;
                const currentChar = text.charAt(pointer - 1);
                const delay = /[.!?]/.test(currentChar) ? 90 : currentChar === ' ' ? 25 : 45;
                setTimeout(type, delay);
            }
        };

        setTimeout(type, index * 180);
    });

    document.querySelectorAll('.glitch-text').forEach((element) => {
        element.addEventListener('mouseenter', () => {
            const targetNode = element.querySelector(`[data-lang-${getCurrentLanguage()}]`) || element;
            const stableText = targetNode.textContent;
            const chars = '!<>-_\\/[]{}=+*?#';
            let iterations = 0;
            const interval = setInterval(() => {
                targetNode.textContent = stableText
                    .split('')
                    .map((char, idx) => (idx < iterations ? stableText[idx] : chars[Math.floor(Math.random() * chars.length)]))
                    .join('');
                iterations += 1 / 3;
                if (iterations >= stableText.length) {
                    clearInterval(interval);
                    targetNode.textContent = stableText;
                }
            }, 25);
        });
    });

    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.12 });

    document.querySelectorAll('.fade-in-up').forEach((element) => {
        revealObserver.observe(element);
    });
}

function initScrollEffects() {
    const scrollProgress = document.querySelector('.scroll-progress');
    const updateScrollProgress = () => {
        if (!scrollProgress) {
            return;
        }
        const scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
        const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const progress = scrollHeight > 0 ? (scrollTop / scrollHeight) * 100 : 0;
        scrollProgress.style.width = `${progress}%`;
    };

    const applyParallax = () => {
        if (window.innerWidth <= 768) {
            document.querySelectorAll('.parallax').forEach((element) => {
                element.style.transform = '';
            });
            return;
        }
        const scrolled = window.pageYOffset;
        document.querySelectorAll('.parallax').forEach((element) => {
            const speed = Number(element.dataset.speed || 0.35);
            element.style.transform = `translateY(${-scrolled * speed}px)`;
        });
    };

    window.addEventListener('scroll', () => {
        updateScrollProgress();
        applyParallax();
    }, { passive: true });

    window.addEventListener('resize', applyParallax);
    updateScrollProgress();
}

function initLogout() {
    document.querySelectorAll('.logout-btn').forEach((btn) => {
        btn.addEventListener('click', async (event) => {
            event.preventDefault();
            try {
                const response = await fetch('/api/auth/logout', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                });
                if (response.ok || response.status === 401) {
                    window.location.href = '/login';
                }
            } catch (error) {
                console.error('Logout failed:', error);
                window.location.href = '/login';
            }
        });
    });
}

window.SereinI18n = {
    setLanguage,
    t: (key) => translate(key),
    setText: (element, key) => {
        if (!element) {
            return;
        }
        element.dataset.i18n = key;
        setI18nText(element, key, SEREIN_TRANSLATION_CACHE[getCurrentLanguage()] || {});
    },
    apply: () => applyTranslatedAttributes(SEREIN_TRANSLATION_CACHE[getCurrentLanguage()] || {}),
};

document.addEventListener('DOMContentLoaded', () => {
    initLanguageSwitcher();
    initNavigation();
    initAnimations();
    initScrollEffects();
    initLogout();
});
