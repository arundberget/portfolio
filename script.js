document.addEventListener('DOMContentLoaded', () => {
    const navItems = document.querySelectorAll('nav li');
    navItems.forEach((item, index) => {
        item.style.setProperty('--index', index + 1);
    });

    const sectionObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'fadeInUp 0.8s ease forwards';
                sectionObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.2 });

    document.querySelectorAll('section').forEach(section => {
        sectionObserver.observe(section);
    });

    const goToTopButton = document.querySelector('.go-to-top');
    window.addEventListener('scroll', () => {
        goToTopButton?.classList.toggle('visible', window.pageYOffset > 300);
    });

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            document.querySelector(targetId)?.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        });
    });

    document.querySelectorAll('.project').forEach(project => {
        project.addEventListener('mouseenter', () => {
            project.style.transform = 'translateY(-5px)';
        });
        project.addEventListener('mouseleave', () => {
            project.style.transform = 'translateY(0)';
        });
    });

    let lastScroll = 0;
    const header = document.querySelector('header');
    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;

        if (currentScroll > lastScroll && currentScroll > 100) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        lastScroll = currentScroll;
    });

    document.querySelectorAll('input, textarea').forEach(input => {
        input.addEventListener('focus', () => {
            input.parentElement.classList.add('focused');
        });
        input.addEventListener('blur', () => {
            if (!input.value) {
                input.parentElement.classList.remove('focused');
            }
        });
    });

    const contactForm = document.getElementById('contact-form');
    if (contactForm) {
        contactForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitButton = contactForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.textContent;
            submitButton.textContent = 'Sending...';
            submitButton.disabled = true;

            try {
                const formData = new FormData(contactForm);
                const response = await fetch(contactForm.action, {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    showFeedback(true, 'Thank you for your message!');
                    contactForm.reset();
                } else {
                    showFeedback(false, 'An error occurred. Please try again later.');
                    console.error('Form submission error:', response.status);
                }
            } catch (error) {
                showFeedback(false, 'An error occurred. Please try again later.');
                console.error('Form submission error:', error);
            } finally {
                submitButton.textContent = originalButtonText;
                submitButton.disabled = false;
            }
        });
    }
});

function showFeedback(isSuccess, message) {
    const existingFeedback = document.querySelector('.form-feedback');
    if (existingFeedback) {
        existingFeedback.remove();
    }
    const feedbackDiv = document.createElement('div');
    feedbackDiv.className = `form-feedback ${isSuccess ? 'success' : 'error'}`;
    feedbackDiv.textContent = message;

    const contactForm = document.getElementById('contact-form');
    contactForm.parentNode.insertBefore(feedbackDiv, contactForm.nextSibling);

    setTimeout(() => {
        feedbackDiv.remove();
    }, 5000);
}
