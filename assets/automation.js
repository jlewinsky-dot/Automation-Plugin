console.info('[ACF Automation] JS loaded.:', (window.ARAutomation && window.ARAutomation.bakeVersion) || 'n/a');
(function () {
  function onReady(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn, { once: true });
    } else {
      fn();
    }
  }

  onReady(function () {
    (function cleanGFProtectParam(){
      if (!('history' in window) || !('replaceState' in history)) return;
      const url = new URL(window.location.href);
      if (url.searchParams.has('gf_protect_submission')) {
        url.searchParams.delete('gf_protect_submission');
        const qs = url.searchParams.toString();
        history.replaceState(null, document.title, url.pathname + (qs ? '?' + qs : '') + window.location.hash);
      }
    })();

    const root = document.querySelector('.custom-page') || document;

    const siteHeader = root.querySelector('#site-header');
    const navToggle  = root.querySelector('.nav-toggle');
    const primaryNav = root.querySelector('#primary-nav');

    function stripTestimonialsNav() {
      if (!primaryNav) return;
      if (!window.matchMedia('(max-width: 880px)').matches) return;
      primaryNav.querySelectorAll('a').forEach(a => {
        try {
          const href = (a.getAttribute('href') || '').toLowerCase();
          const text = (a.textContent || '').trim().toLowerCase();
          if (href.includes('#testimonial') || text === 'testimonials' || text === 'testimonial') {
            const li = a.closest('li');
            if (li && !li.__removedTestimonials) {
              li.__removedTestimonials = true;
              li.parentNode && li.parentNode.removeChild(li);
            }
          }
        } catch(e) { /* ignore */ }
      });
    }
    stripTestimonialsNav();
    let stnTO; window.addEventListener('resize', () => { clearTimeout(stnTO); stnTO = setTimeout(stripTestimonialsNav, 120); });

    function updateHeaderShadow() {
      if (!siteHeader) return;
      if (window.scrollY > 4) siteHeader.classList.add('scrolled');
      else siteHeader.classList.remove('scrolled');
    }
    window.addEventListener('scroll', updateHeaderShadow, { passive: true });
    updateHeaderShadow();

    if (navToggle && primaryNav) {
      navToggle.addEventListener('click', () => {
        const expanded = navToggle.getAttribute('aria-expanded') === 'true';
        navToggle.setAttribute('aria-expanded', String(!expanded));
        primaryNav.classList.toggle('open', !expanded);
      });

      primaryNav.querySelectorAll('a').forEach(a => {
        a.addEventListener('click', () => {
          if (primaryNav.classList.contains('open')) {
            primaryNav.classList.remove('open');
            navToggle.setAttribute('aria-expanded', 'false');
          }
        });
      });

      document.addEventListener('click', (e) => {
        if (!primaryNav.classList.contains('open')) return;
        if (e.target === navToggle || navToggle.contains(e.target)) return;
        if (primaryNav.contains(e.target)) return;
        primaryNav.classList.remove('open');
        navToggle.setAttribute('aria-expanded', 'false');
      });

      document.addEventListener('keydown', (e) => {
        if (e.key !== 'Tab' || !primaryNav.classList.contains('open')) return;
        const focusables = primaryNav.querySelectorAll('a,button');
        if (!focusables.length) return;
        const first = focusables[0];
        const last = focusables[focusables.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      });
    }

    (function initTestimonials(){
      const container = root.querySelector('.testimonial-section');
      if(!container) return;
      let slides = container.querySelectorAll('.testimonial-slide');
      if(!slides.length) return;
      let currentSlide = 0;

      function showSlide(index){
        if(!slides.length) return;
        if(index < 0) index = 0;
        if(index >= slides.length) index = slides.length - 1;
        slides.forEach(s=>s.classList.remove('active'));
        slides[index].classList.add('active');
        currentSlide = index;
      }

      function next(){
        if(!slides.length) return;
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
      }
      function prev(){
        if(!slides.length) return;
        currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        showSlide(currentSlide);
      }

      const start = Array.from(slides).findIndex(s=>s.classList.contains('active'));
      if(start >= 0){ currentSlide = start; } else { showSlide(0); }

      if(!container.__testimonialInterval){
        container.__testimonialInterval = setInterval(next, 8500);
      }

      function bindArrows(){
        const left = container.querySelector('.arrow-left');
        const right = container.querySelector('.arrow-right');
        [left,right].forEach(el=>{
          if(!el) return;
          el.style.display = 'flex';
          el.style.alignItems = 'center';
          el.style.justifyContent = 'center';
          el.style.visibility = 'visible';
          el.style.opacity = '1';
          if(!el.__bound){
            el.addEventListener('click', el.classList.contains('arrow-left') ? prev : next);
            el.addEventListener('keydown', (e)=>{
              if(e.key === 'Enter' || e.key === ' '){ e.preventDefault(); (el.classList.contains('arrow-left')?prev:next)(); }
            });
            el.setAttribute('role','button');
            el.setAttribute('tabindex','0');
            el.__bound = true;
          }
        });
      }
      bindArrows();

      if(!container.__testimonialObserver){
        const mo = new MutationObserver(()=>{
          slides = container.querySelectorAll('.testimonial-slide');
          bindArrows();
        });
        mo.observe(container, {childList:true, subtree:true});
        container.__testimonialObserver = mo;
      }
    })();

    const collapsibles = root.querySelectorAll('.collapsible');
    collapsibles.forEach((btn) => {
      const content = btn.nextElementSibling;
      const icon = btn.querySelector('.icon');
      if (!content) return;

      content.style.maxHeight = null;

      btn.addEventListener('click', function () {
        const open = btn.classList.toggle('active');
        if (open) {
          content.style.maxHeight = content.scrollHeight + 'px';
          if (icon) icon.textContent = '–';
        } else {
          content.style.maxHeight = null;
          if (icon) icon.textContent = '+';
        }
      });
    });

    window.addEventListener('resize', () => {
      root.querySelectorAll('.collapsible.active').forEach((openBtn) => {
        const content = openBtn.nextElementSibling;
        if (content) content.style.maxHeight = content.scrollHeight + 'px';
      });
    });

    const container = root.querySelector('.cards-wheel__container');
    if (container) {
      const magnifier = document.createElement('div');
      magnifier.className = 'magnifier';
      document.body.appendChild(magnifier);

      root.querySelectorAll('.cards-wheel__image img').forEach((img) => {
        img.addEventListener('mouseenter', () => {
          magnifier.style.display = 'block';
          magnifier.style.backgroundImage = `url('${img.src}')`;
          const w = img.naturalWidth || img.width;
          const h = img.naturalHeight || img.height;
          magnifier.style.backgroundSize = `${w * 2}px ${h * 2}px`;
          img.parentElement.classList.add('hovered');
        });

        img.addEventListener('mousemove', (e) => {
          const rect = img.getBoundingClientRect();
          const x = e.clientX - rect.left;
          const y = e.clientY - rect.top;
          const px = (x / rect.width) * 100;
          const py = (y / rect.height) * 100;
          magnifier.style.left = `${e.pageX}px`;
          magnifier.style.top = `${e.pageY}px`;
          magnifier.style.backgroundPosition = `${px}% ${py}%`;
        });

        img.addEventListener('mouseleave', () => {
          magnifier.style.display = 'none';
          img.parentElement.classList.remove('hovered');
        });
      });

      container.addEventListener(
        'wheel',
        (e) => {
          e.preventDefault();
          container.scrollBy({ left: e.deltaY * 2, behavior: 'smooth' });
        },
        { passive: false }
      );

      let isDown = false,
        startX = 0,
        scrollLeft = 0;
      container.addEventListener('mousedown', (e) => {
        isDown = true;
        startX = e.pageX - container.getBoundingClientRect().left;
        scrollLeft = container.scrollLeft;
        container.classList.add('dragging');
      });
      container.addEventListener('mouseleave', () => {
        isDown = false;
        container.classList.remove('dragging');
      });
      container.addEventListener('mouseup', () => {
        isDown = false;
        container.classList.remove('dragging');
      });
      container.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - container.getBoundingClientRect().left;
        const walk = (x - startX) * 1.5;
        container.scrollLeft = scrollLeft - walk;
      });

      container.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
          e.preventDefault();
          const delta = e.key === 'ArrowRight' ? 200 : -200;
          container.scrollBy({ left: delta, behavior: 'smooth' });
        }
      });
    }

    (function serviceAreasToggle(){
      const toggleBtn = document.getElementById('service-areas-toggle');
      if(!toggleBtn) return;
      const grid = document.getElementById('service-areas-grid');
      const extraWrapper = document.querySelector('#service-areas-grid .service-area-extra');
      if(!grid || !extraWrapper) return;
      toggleBtn.addEventListener('click', () => {
        const isHidden = extraWrapper.hasAttribute('hidden');
        const isMobile = window.matchMedia('(max-width: 600px)').matches;
        if(isHidden){
          extraWrapper.removeAttribute('hidden');
          extraWrapper.classList.add('revealed');
          if(isMobile) grid.classList.add('open');
          toggleBtn.textContent = 'View Less';
          toggleBtn.setAttribute('aria-expanded','true');
        } else {
          extraWrapper.classList.remove('revealed');
          extraWrapper.setAttribute('hidden','');
          if(isMobile) grid.classList.remove('open');
          toggleBtn.textContent = 'View More';
          toggleBtn.setAttribute('aria-expanded','false');
        }
      });
    })();

    // ===== Hero Gravity Form Error Expansion =====
    (function heroGFErrorWatcher(){
      const hero = document.querySelector('.home-new');
      if(!hero) return;
      // Observe DOM mutations for GF error summary or field errors
      const observeTargets = hero.querySelectorAll('.gform_wrapper');
      if(!observeTargets.length) return;
      const applyState = () => {
        let hasErrors = false;
        observeTargets.forEach(wrapper => {
          if (wrapper.querySelector('.validation_error, .gform_validation_errors, .gfield_error')) hasErrors = true;
        });
        hero.classList.toggle('gform-errors-present', hasErrors);
        if (hasErrors) {
          // Ensure submit button region visible
          const lastField = hero.querySelector('.gform_wrapper form .gform_body .gfield:last-of-type');
          const footer   = hero.querySelector('.gform_wrapper .gform_footer, .gform_wrapper .gform_page_footer');
          const target = footer || lastField;
          if (target) {
            // Delay to allow GF to finish layout
            setTimeout(()=>{
              try { target.scrollIntoView({behavior:'smooth', block:'end'}); } catch(e) {}
            }, 60);
          }
        }
      };
      const mo = new MutationObserver((muts)=>{
        let relevant = false;
        muts.forEach(m => {
          if (m.addedNodes.length || m.removedNodes.length) relevant = true;
          if (m.type === 'attributes') relevant = true;
        });
        if (relevant) applyState();
      });
      observeTargets.forEach(t => mo.observe(t, {childList:true, subtree:true, attributes:true}));
      applyState();
    })();

    (function quotePopup(){
      const modal  = document.getElementById('quote-popup');
      if (!modal) return;

      const dialog   = modal.querySelector('.quote-popup');

      if (dialog) {
        dialog.style.flex = '0 0 auto';
        dialog.style.width = '100%';
        dialog.style.maxWidth = '760px';
        dialog.style.boxSizing = 'border-box';
      }

      const closeBtn = document.getElementById('quote-popup-close');
      const iframe   = modal.querySelector('.quote-popup-iframe-wrapper iframe');
      const triggers = document.querySelectorAll('[data-quote-trigger], .nav-quote-btn');

      if (!triggers.length) {
        console.warn('[ACF Automation] No quote popup triggers found. Add data-quote-trigger to a button.');
      }

      let lastFocused = null;

      function openModal(evt){
        if (evt) evt.preventDefault();
        if (modal.classList.contains('open')) return;

        lastFocused = document.activeElement;
        modal.classList.add('open');
        const iframe = modal.querySelector('.quote-popup-iframe-wrapper iframe');
        if (iframe) {
          iframe.style.minHeight = '560px';
          iframe.style.maxHeight = 'none';
          iframe.style.height = 'auto';
          iframe.removeAttribute('height');
          iframe.setAttribute('scrolling','no');
        }

        modal.setAttribute('aria-hidden','false');

        if (iframe && !iframe.getAttribute('src')) {
          const src = (window.ARAutomation && ARAutomation.formUrl)
            ? ARAutomation.formUrl
            : iframe.getAttribute('data-src') || '';
          if (src) iframe.setAttribute('src', src);
        }

        if (iframe) {
          iframe.style.minHeight = '560px';
          iframe.style.maxHeight = 'none';
          iframe.style.height = 'auto';
          iframe.removeAttribute('height');
          iframe.setAttribute('scrolling', 'no');

          if (!modal.__gfMsgBound) {
            modal.__gfMsgBound = true;
            window.addEventListener('message', function onGFResize(e) {
              const frame = modal.querySelector('.quote-popup-iframe-wrapper iframe');
              if (!frame) return;

              const data = typeof e.data === 'string'
                ? (() => { try { return JSON.parse(e.data); } catch { return null; } })()
                : e.data;
              if (!data) return;

              const looksLikeGF =
                ('gform' in data) ||
                (data.action && /gform|gf/i.test(data.action)) ||
                ('gf_iframe' in data) ||
                ('gf_height' in data) ||
                ('gf_form_id' in data);

              if (!looksLikeGF) return;
              if (e.source !== frame.contentWindow) return;

              const MIN = 560;
              const requested = Number(data.height || data.gf_height || 0) || MIN;
              frame.style.height = Math.max(MIN, requested) + 'px';

              if (!modal.__gfWrapped) {
                try {
                  const doc = frame.contentDocument || frame.contentWindow.document;
                  if (doc) {
                    const gfFooters = doc.querySelectorAll('.gform_footer, .gform_page_footer');
                    gfFooters.forEach(f => {
                      if (f.closest('.quote-popup-footer')) return;
                      const wrap = doc.createElement('div');
                      wrap.className = 'quote-popup-footer';
                      f.parentNode.insertBefore(wrap, f);
                      wrap.appendChild(f);
                    });
                    modal.__gfWrapped = true;
                  }
                } catch(e) { /* cross-domain, ignore */ }
              }
            }, { passive: true });
          }
        }

        requestAnimationFrame(() => { if (dialog) dialog.focus({ preventScroll: true }); });
        document.addEventListener('keydown', onKeydown);
      }

      function closeModal(){
        if (!modal.classList.contains('open')) return;
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden','true');
        document.removeEventListener('keydown', onKeydown);
        if (lastFocused && typeof lastFocused.focus === 'function') {
          lastFocused.focus({ preventScroll: true });
        }
      }

      function onKeydown(e){
        if (e.key === 'Escape') { closeModal(); }
        if (e.key === 'Tab')    { trapFocus(e); }
      }

      function trapFocus(e){
        const focusables = modal.querySelectorAll('button, [href], iframe, input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (!focusables.length) return;
        const list  = Array.from(focusables).filter(el => !el.hasAttribute('disabled') && el.getAttribute('aria-hidden') !== 'true');
        if (!list.length) return;
        const first = list[0];
        const last  = list[list.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      }

      triggers.forEach(t => {
        t.addEventListener('click', openModal);
        t.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            openModal(e);
          }
        });
        if (t.tagName === 'BUTTON' && !t.hasAttribute('type')) t.setAttribute('type','button');
      });

      if (closeBtn) closeBtn.addEventListener('click', closeModal);
      modal.addEventListener('mousedown', e => { if (e.target === modal) closeModal(); });

      window.ARQuotePopup = { open: openModal, close: closeModal };
    })();
  });
})();
