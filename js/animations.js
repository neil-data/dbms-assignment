/**
 * CEMS - Restrained Editorial GSAP Animation Engine (js/animations.js)
 * Clean, subtle, Awwwards-inspired typography and section reveals.
 */

export function initAnimations() {
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const gsapInstance = window.gsap || (typeof gsap !== 'undefined' ? gsap : null);
  const ScrollTriggerInstance = window.ScrollTrigger || (typeof ScrollTrigger !== 'undefined' ? ScrollTrigger : null);

  if (!gsapInstance || prefersReducedMotion) {
    document.querySelectorAll(
      '.hero-headline, .hero-description, .hero-cta-group, .hero-visual-container, .club-editorial-card, .event-editorial-card'
    ).forEach(el => {
      el.style.opacity = '1';
      el.style.transform = 'none';
    });
    return;
  }

  if (ScrollTriggerInstance) {
    try {
      gsapInstance.registerPlugin(ScrollTriggerInstance);
    } catch (e) {
      console.warn('ScrollTrigger register:', e);
    }
  }

  // 1. Page Load Hero Typography Stagger
  const heroTl = gsapInstance.timeline({ defaults: { ease: 'power3.out' } });

  const eyebrow = document.querySelector('.eyebrow-pill');
  const headline = document.querySelector('.hero-headline');
  const description = document.querySelector('.hero-description');
  const ctas = document.querySelector('.hero-cta-group');
  const visual = document.querySelector('.hero-visual-container');

  if (eyebrow) {
    heroTl.fromTo(eyebrow, 
      { opacity: 0, y: 15 }, 
      { opacity: 1, y: 0, duration: 0.6, delay: 0.1 }
    );
  }

  if (headline) {
    heroTl.fromTo(headline,
      { opacity: 0, y: 25 },
      { opacity: 1, y: 0, duration: 0.8 },
      '-=0.4'
    );
  }

  if (description) {
    heroTl.fromTo(description,
      { opacity: 0, y: 20 },
      { opacity: 1, y: 0, duration: 0.7 },
      '-=0.5'
    );
  }

  if (ctas) {
    heroTl.fromTo(ctas,
      { opacity: 0, y: 15 },
      { opacity: 1, y: 0, duration: 0.6 },
      '-=0.4'
    );
  }

  if (visual) {
    heroTl.fromTo(visual,
      { opacity: 0, scale: 0.94 },
      { opacity: 1, scale: 1, duration: 1.1, ease: 'expo.out' },
      '-=0.8'
    );

    // Subtle celestial orbit rotation
    const orbit = document.querySelector('.arc-orbit-line');
    if (orbit) {
      gsapInstance.to(orbit, {
        rotation: 360,
        transformOrigin: '50% 50%',
        duration: 90,
        repeat: -1,
        ease: 'none'
      });
    }

    // Gentle lime arc breath
    const limeArc = document.querySelector('.arc-gauge-lime');
    if (limeArc) {
      gsapInstance.to(limeArc, {
        opacity: 0.8,
        duration: 2.5,
        yoyo: true,
        repeat: -1,
        ease: 'sine.inOut'
      });
    }
  }

  // 2. ScrollTrigger Section Reveals
  if (ScrollTriggerInstance) {
    // Featured Clubs Grid
    const clubsGrid = document.querySelector('.clubs-editorial-grid');
    if (clubsGrid) {
      gsapInstance.fromTo(clubsGrid.children,
        { opacity: 0, y: 30 },
        {
          opacity: 1,
          y: 0,
          duration: 0.7,
          stagger: 0.1,
          ease: 'power2.out',
          scrollTrigger: {
            trigger: clubsGrid,
            start: 'top 85%',
            once: true
          }
        }
      );
    }

    // Workflows Steps
    const stepsGrid = document.querySelector('.workflow-steps-grid');
    if (stepsGrid) {
      gsapInstance.fromTo(stepsGrid.children,
        { opacity: 0, y: 25 },
        {
          opacity: 1,
          y: 0,
          duration: 0.7,
          stagger: 0.15,
          ease: 'power2.out',
          scrollTrigger: {
            trigger: stepsGrid,
            start: 'top 85%',
            once: true
          }
        }
      );
    }

    // Events Grid
    const eventsGrid = document.querySelector('.events-editorial-grid');
    if (eventsGrid) {
      gsapInstance.fromTo(eventsGrid.children,
        { opacity: 0, y: 25 },
        {
          opacity: 1,
          y: 0,
          duration: 0.6,
          stagger: 0.08,
          ease: 'power2.out',
          scrollTrigger: {
            trigger: eventsGrid,
            start: 'top 85%',
            once: true
          }
        }
      );
    }
  }
}
