const tutorialSteps = [
    {
        selector: '#nav-home-link',
        title: 'Accueil WayFinder',
        text: 'Bienvenue dans l\'espace créateur. Cliquez sur le logo pour revenir à la page d\'accueil à tout moment.',
    },
    {
        selector: '#nav-hunts-link',
        title: 'Chasses au trésor',
        text: 'Créez, modifiez et publiez vos chasses au trésor, ajoutez vos énigmes et suivez le classement des joueurs.',
    },
    {
        selector: '#nav-teams-link',
        title: 'Équipes',
        text: 'Créez ou rejoignez une équipe de créateurs. Vous devez faire partie d\'une équipe pour créer une chasse.',
    },
    {
        selector: '#userDropdown',
        title: 'Menu utilisateur',
        text: 'Accédez à votre profil, vos paramètres et vos notifications. Vous pouvez aussi relancer ce tutoriel ici.',
    },
];

document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const overlay = document.getElementById('tutorial-overlay');
    const spotlight = document.getElementById('tutorial-spotlight');
    const tooltip = document.getElementById('tutorial-tooltip');
    const stepCounter = document.getElementById('tutorial-step-count');
    const titleEl = document.getElementById('tutorial-title');
    const textEl = document.getElementById('tutorial-text');
    const iconCloseBtn = document.getElementById('tutorial-icon-close');
    const closeBtn = document.getElementById('tutorial-btn-close');
    const prevBtn = document.getElementById('tutorial-btn-prev');
    const nextBtn = document.getElementById('tutorial-btn-next');
    const startButton = document.getElementById('start-tutorial-btn');

    if (!overlay || !spotlight || !tooltip || !body.dataset.tutorialCompleteUrl) {
        return;
    }

    let currentStep = 0;
    let activeSteps = [];
    let isOpen = false;
    let openedMobileNav = false;

    function ensureVisible(target) {
        const navbarCollapse = document.getElementById('navbarNav');
        if (navbarCollapse && navbarCollapse.contains(target) && !navbarCollapse.classList.contains('show')) {
            if (window.innerWidth < 992) {
                navbarCollapse.classList.add('show');
                openedMobileNav = true;
            }
        }
    }

    function positionElements(target) {
        const padding = 6;
        const rect = target.getBoundingClientRect();

        spotlight.style.top = `${rect.top - padding}px`;
        spotlight.style.left = `${rect.left - padding}px`;
        spotlight.style.width = `${rect.width + padding * 2}px`;
        spotlight.style.height = `${rect.height + padding * 2}px`;

        const tooltipRect = tooltip.getBoundingClientRect();
        const margin = 12;

        let top = rect.bottom + padding + margin;
        if (top + tooltipRect.height > window.innerHeight - margin) {
            top = rect.top - padding - margin - tooltipRect.height;
        }
        if (top < margin) {
            top = margin;
        }

        let left = rect.left + rect.width / 2 - tooltipRect.width / 2;
        if (left < margin) {
            left = margin;
        }
        if (left + tooltipRect.width > window.innerWidth - margin) {
            left = window.innerWidth - tooltipRect.width - margin;
        }

        tooltip.style.top = `${top}px`;
        tooltip.style.left = `${left}px`;
    }

    function showStep(index) {
        currentStep = index;
        const step = activeSteps[currentStep];
        const target = document.querySelector(step.selector);

        if (!target) {
            return;
        }

        ensureVisible(target);

        stepCounter.textContent = `${currentStep + 1} / ${activeSteps.length}`;
        titleEl.textContent = step.title;
        textEl.textContent = step.text;

        prevBtn.disabled = currentStep === 0;
        nextBtn.textContent = currentStep === activeSteps.length - 1 ? 'Terminer' : 'Suivant';

        positionElements(target);
    }

    function handleResizeOrScroll() {
        if (!isOpen) {
            return;
        }
        const step = activeSteps[currentStep];
        const target = document.querySelector(step.selector);
        if (target) {
            positionElements(target);
        }
    }

    function handleKeydown(event) {
        if (!isOpen) {
            return;
        }
        if (event.key === 'Escape') {
            closeTutorial();
        } else if (event.key === 'ArrowRight') {
            if (currentStep < activeSteps.length - 1) {
                showStep(currentStep + 1);
            } else {
                closeTutorial();
            }
        } else if (event.key === 'ArrowLeft' && currentStep > 0) {
            showStep(currentStep - 1);
        }
    }

    function markCompleted() {
        if (body.dataset.tutorialCompleted === 'true') {
            return;
        }

        body.dataset.tutorialCompleted = 'true';

        fetch(body.dataset.tutorialCompleteUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': body.dataset.tutorialCsrfToken,
                Accept: 'application/json',
            },
            body: JSON.stringify({ _token: body.dataset.tutorialCsrfToken }),
        }).catch(() => {});
    }

    function startTutorial() {
        if (isOpen) {
            return;
        }

        activeSteps = tutorialSteps.filter((step) => document.querySelector(step.selector));
        if (activeSteps.length === 0) {
            return;
        }

        isOpen = true;
        overlay.classList.remove('d-none');
        spotlight.classList.remove('d-none');
        tooltip.classList.remove('d-none');

        window.addEventListener('resize', handleResizeOrScroll);
        window.addEventListener('scroll', handleResizeOrScroll);
        document.addEventListener('keydown', handleKeydown);
        showStep(0);
    }

    function closeTutorial() {
        if (!isOpen) {
            return;
        }

        isOpen = false;
        window.removeEventListener('resize', handleResizeOrScroll);
        window.removeEventListener('scroll', handleResizeOrScroll);
        document.removeEventListener('keydown', handleKeydown);

        overlay.classList.add('d-none');
        spotlight.classList.add('d-none');
        tooltip.classList.add('d-none');

        if (openedMobileNav) {
            const navbarCollapse = document.getElementById('navbarNav');
            if (navbarCollapse) {
                navbarCollapse.classList.remove('show');
            }
            openedMobileNav = false;
        }

        markCompleted();
    }

    iconCloseBtn.addEventListener('click', closeTutorial);
    closeBtn.addEventListener('click', closeTutorial);

    prevBtn.addEventListener('click', () => {
        if (currentStep > 0) {
            showStep(currentStep - 1);
        }
    });

    nextBtn.addEventListener('click', () => {
        if (currentStep < activeSteps.length - 1) {
            showStep(currentStep + 1);
        } else {
            closeTutorial();
        }
    });

    if (startButton) {
        startButton.addEventListener('click', (event) => {
            event.preventDefault();
            const dropdownToggle = document.getElementById('userDropdown');
            if (dropdownToggle && window.bootstrap) {
                const dropdown = window.bootstrap.Dropdown.getInstance(dropdownToggle);
                if (dropdown) {
                    dropdown.hide();
                }
            }
            startTutorial();
        });
    }

    if (body.dataset.tutorialCompleted === 'false') {
        startTutorial();
    }
});
