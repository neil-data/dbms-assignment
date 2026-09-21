/**
 * CEMS - GSAP & ScrollTrigger Animation Engine
 * Centralized cinematic animations, page load timelines, and interactive reveals.
 */

export function initAnimations() {
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Check if GSAP is available globally
  const gsapInstance = window.gsap || (typeof gsap !== 'undefined' ? gsap : null);
  const ScrollTriggerInstance = window.ScrollTrigger || (typeof ScrollTrigger !== 'undefined' ? ScrollTrigger : null);

  if (gsapInstance && ScrollTriggerInstance) {
    try {
      gsapInstance.registerPlugin(ScrollTriggerInstance);
    } catch (e) {
      console.warn('ScrollTrigger registration:', e);
    }
  }

  // Header Scroll Effect
  const header = document.querySelector('.cems-header');
  if (header) {
    const handleScroll = () => {
      if (window.scrollY > 30) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }
    };
    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll();
  }

  // If user prefers reduced motion or GSAP not loaded, ensure all elements are visible
  if (prefersReducedMotion || !gsapInstance) {
    document.querySelectorAll(
      '.hero-eyebrow, .hero-title, .hero-subtitle, .hero-cta-group, .hero-indicators, .event-card, .category-row, .workflow-step, .section-header, .preview-container'
    ).forEach(el => {
      el.style.opacity = '1';
      el.style.transform = 'none';
    });
    return;
  }

  // 1. Cinematic Light Trails Continuous Subtle Movement
  const lightPathsGold = document.querySelectorAll('.light-trail-gold');
  const lightPathsBlue = document.querySelectorAll('.light-trail-blue');
  const flare = document.querySelector('.horizon-flare');
  const spark = document.querySelector('.horizon-spark');
  const nebulaGold = document.querySelector('.glow-nebula-gold');
  const nebulaBlue = document.querySelector('.glow-nebula-blue');

  if (lightPathsGold.length > 0) {
    gsapInstance.to(lightPathsGold, {
      strokeDashoffset: '-=500',
      duration: 24,
      repeat: -1,
      ease: 'none'
    });
  }

  if (lightPathsBlue.length > 0) {
    gsapInstance.to(lightPathsBlue, {
      strokeDashoffset: '+=500',
      duration: 28,
      repeat: -1,
      ease: 'none'
    });
  }

  if (flare && spark) {
    gsapInstance.to(flare, {
      scaleX: 1.15,
      scaleY: 1.25,
      opacity: 0.9,
      duration: 4,
      repeat: -1,
      yoyo: true,
      ease: 'sine.inOut'
    });

    gsapInstance.to(spark, {
      boxShadow: '0 0 35px 12px #ffffff, 0 0 75px 25px #f5cf68, 0 0 130px 50px #38bdf8',
      duration: 3,
      repeat: -1,
      yoyo: true,
      ease: 'sine.inOut'
    });
  }

  if (nebulaGold) {
    gsapInstance.to(nebulaGold, {
      x: 25,
      y: -15,
      opacity: 0.45,
      duration: 8,
      repeat: -1,
      yoyo: true,
      ease: 'sine.inOut'
    });
  }

  if (nebulaBlue) {
    gsapInstance.to(nebulaBlue, {
      x: -25,
      y: 15,
      opacity: 0.5,
      duration: 9,
      repeat: -1,
      yoyo: true,
      ease: 'sine.inOut'
    });
  }

  // 2. Page Load Timeline (Home & Global)
  const heroHeading = document.getElementById('hero-heading');
  if (heroHeading) {
    const heroTL = gsapInstance.timeline({ defaults: { ease: 'power3.out' } });

    heroTL
      .fromTo('.cems-header',
        { y: -30, opacity: 0 },
        { y: 0, opacity: 1, duration: 0.8, delay: 0.1 }
      )
      .fromTo('.hero-eyebrow',
        { opacity: 0, y: -20, scale: 0.96 },
        { opacity: 1, y: 0, scale: 1, duration: 0.75 },
        '-=0.4'
      )
      .fromTo('.hero-title .title-line',
        { opacity: 0, y: 35, filter: 'blur(6px)' },
        { opacity: 1, y: 0, filter: 'blur(0px)', duration: 0.95, stagger: 0.16 },
        '-=0.5'
      )
      .fromTo('.hero-subtitle',
        { opacity: 0, y: 22 },
        { opacity: 1, y: 0, duration: 0.8 },
        '-=0.6'
      )
      .fromTo('.hero-cta-group .btn',
        { opacity: 0, y: 18, scale: 0.98 },
        { opacity: 1, y: 0, scale: 1, duration: 0.65, stagger: 0.1 },
        '-=0.5'
      )
      .fromTo('.hero-indicators .indicator-item',
        { opacity: 0, y: 14 },
        { opacity: 1, y: 0, duration: 0.55, stagger: 0.08 },
        '-=0.4'
      );
  } else {
    // Other pages: header and main content gentle fade-in
    gsapInstance.fromTo('.cems-header',
      { opacity: 0, y: -20 },
      { opacity: 1, y: 0, duration: 0.6, ease: 'power2.out' }
    );
    gsapInstance.fromTo('main',
      { opacity: 0, y: 15 },
      { opacity: 1, y: 0, duration: 0.7, delay: 0.15, ease: 'power2.out' }
    );
  }

  // 3. ScrollTrigger Section Reveals
  if (ScrollTriggerInstance) {
    // Section headers reveal
    document.querySelectorAll('.section-header').forEach(headerEl => {
      gsapInstance.fromTo(headerEl,
        { opacity: 0, y: 30 },
        {
          opacity: 1,
          y: 0,
          duration: 0.8,
          ease: 'power2.out',
          scrollTrigger: {
            trigger: headerEl,
            start: 'top 85%',
            toggleActions: 'play none none none'
          }
        }
      );
    });

    // Workflow steps sequence
    const workflowSteps = document.querySelectorAll('.workflow-grid .workflow-step');
    if (workflowSteps.length > 0) {
      gsapInstance.fromTo(workflowSteps,
        { opacity: 0, y: 35 },
        {
          opacity: 1,
          y: 0,
          duration: 0.75,
          stagger: 0.16,
          ease: 'power2.out',
          scrollTrigger: {
            trigger: '.workflow-grid',
            start: 'top 85%',
            toggleActions: 'play none none none'
          }
        }
      );
    }

    // Category rows reveal
    const categoryRows = document.querySelectorAll('.category-rows .category-row');
    if (categoryRows.length > 0) {
      gsapInstance.fromTo(categoryRows,
        { opacity: 0, x: -25 },
        {
          opacity: 1,
          x: 0,
          duration: 0.65,
          stagger: 0.08,
          ease: 'power2.out',
          scrollTrigger: {
            trigger: '.category-rows',
            start: 'top 85%',
            toggleActions: 'play none none none'
          }
        }
      );
    }

    // Architecture preview container
    const previewContainer = document.querySelector('.preview-container');
    if (previewContainer) {
      gsapInstance.fromTo(previewContainer,
        { opacity: 0, y: 45 },
        {
          opacity: 1,
          y: 0,
          duration: 0.9,
          ease: 'power3.out',
          scrollTrigger: {
            trigger: previewContainer,
            start: 'top 82%',
            toggleActions: 'play none none none'
          }
        }
      );
    }
  }

  // 4. Subtle Page Transitions
  setupPageTransitions(gsapInstance);
}

function setupPageTransitions(gsapInstance) {
  if (!gsapInstance) return;

  document.querySelectorAll('a[href]').forEach(link => {
    const href = link.getAttribute('href');
    // Only apply to internal html links, avoid hash links and external protocols
    if (
      href &&
      !href.startsWith('#') &&
      !href.startsWith('mailto:') &&
      !href.startsWith('tel:') &&
      !href.startsWith('javascript:') &&
      !link.hasAttribute('target') &&
      !link.classList.contains('no-transition')
    ) {
      link.addEventListener('click', (e) => {
        // Allow default if modifier keys are pressed
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

        const targetUrl = link.href;
        if (!targetUrl || targetUrl === window.location.href) return;

        // Verify same origin
        if (link.origin === window.location.origin) {
          e.preventDefault();
          gsapInstance.to('body', {
            opacity: 0,
            duration: 0.25,
            ease: 'power1.in',
            onComplete: () => {
              window.location.href = targetUrl;
            }
          });
        }
      });
    }
  });

  // Ensure body fades in on initial load
  gsapInstance.fromTo('body',
    { opacity: 0 },
    { opacity: 1, duration: 0.35, ease: 'power1.out' }
  );
}
